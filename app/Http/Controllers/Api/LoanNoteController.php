<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Kasbon;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LoanNoteController extends Controller {
    public function companies() { return response()->json(Company::orderBy('nama')->get(['id','nama'])); }
    public function index(Request $request) {
        $records=Kasbon::where('user_id',$request->user()->id)->with(['company','user'])->orderByDesc('created_at')->orderByDesc('id')->paginate(20);
        $records->through(fn($r)=>$this->format($r,$request));
        return response()->json($records);
    }
    public function show(Request $request,int $id) {
        return response()->json($this->format(Kasbon::where('user_id',$request->user()->id)->findOrFail($id),$request));
    }
    public function store(Request $request) {
        $data=$this->validated($request);
        $user=$request->user(); $department=$user->departments()->first();
        abort_unless($department,422,'Akun belum memiliki department.');
        $boss=$user->profile?->atasan;
        $finance=$user->jabatan===Kasbon::LEVEL2_JABATAN;
        $manager=$user->isSuperuser();
        $skip=!$boss || $boss->id===$user->id;
        $level=$finance?3:(($manager || $skip)?2:1);
        $selfManager=$finance || (!$manager && $skip);
        $path=$request->hasFile('lampiran')?$request->file('lampiran')->store('kasbon','public'):null;
        if($request->hasFile('lampiran')) abort_unless($path,500,'Lampiran gagal disimpan.');
        try {
            $record=DB::transaction(function() use($data,$user,$department,$boss,$finance,$level,$selfManager,$path) {
                $record=Kasbon::create(array_merge($data,[
                    'user_id'=>$user->id,'department_id'=>$department->id,'lampiran'=>$path,
                    'status'=>$finance?'Approved':'Pending Approval','approval_level'=>$level,
                    'approved_by_manager'=>$selfManager?$user->id:null,'approved_manager_at'=>$selfManager?now():null,
                    'approved_by'=>$finance?$user->id:null,'approved_at'=>$finance?now():null,
                ]));
                $recipients=$finance?collect([$user]):($level===1?collect([$boss]):User::where('jabatan',Kasbon::LEVEL2_JABATAN)->get());
                foreach($recipients as $recipient) Notification::make()->title($finance?'Kasbon Disetujui Otomatis':'Pengajuan Kasbon Baru')
                    ->body("{$user->name} mengajukan kasbon.")->icon('heroicon-o-banknotes')->sendToDatabase($recipient);
                return $record;
            });
        } catch (\Throwable $e) { if($path) Storage::disk('public')->delete($path); throw $e; }
        return response()->json(['data'=>$this->format($record,$request)],201);
    }
    public function update(Request $request,int $id) {
        $path=null;
        try {
            $record=DB::transaction(function() use($request,$id,&$path) {
                $record=Kasbon::where('user_id',$request->user()->id)->lockForUpdate()->findOrFail($id);
                abort_unless($record->canBeEditedBy($request->user()),422,'Permohonan tidak dapat diedit pada status ini.');
                $data=$this->validated($request,$record); unset($data['lampiran']);
                if ($request->hasFile('lampiran')) {
                    $path=$request->file('lampiran')->store('kasbon','public');
                    abort_unless($path,500,'Lampiran gagal disimpan.'); $data['lampiran']=$path;
                }
                $record->update($data); return $record;
            });
        } catch (\Throwable $e) { if($path) Storage::disk('public')->delete($path); throw $e; }
        return response()->json(['data'=>$this->format($record,$request)]);
    }
    public function cancel(Request $request,int $id) {
        return DB::transaction(function() use($request,$id) {
            $record=Kasbon::where('user_id',$request->user()->id)->lockForUpdate()->findOrFail($id);
            abort_unless($record->cancel($request->user()),422,'Permohonan tidak dapat dibatalkan.');
            return response()->json(['data'=>$this->format($record,$request)]);
        });
    }
    private function validated(Request $request,?Kasbon $record=null): array {
        $data=$request->validate([
            'company_id'=>['required','integer',Rule::exists('companies','id')->whereNull('deleted_at')],
            'tanggal'=>['required','date_format:Y-m-d'],
            'jumlah_dana'=>['required','numeric','min:0.01','max:999999999999.99','regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'keterangan'=>['nullable','string','max:5000'], 'informasi_transfer'=>['nullable','string','max:5000'],
            'lampiran'=>['nullable','file','mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx','max:10240'],
        ]);
        $parts=explode('.',(string)$data['jumlah_dana']);
        $whole=(int)$parts[0]; $cents=(int)str_pad($parts[1]??'',2,'0');
        $data['jumlah_dana']=$whole.'.'.str_pad((string)$cents,2,'0',STR_PAD_LEFT);
        $formatter=new \NumberFormatter('en',\NumberFormatter::SPELLOUT);
        $data['terbilang']=ucwords($formatter->format($whole)).' Rupiah'.($cents?' and '.ucwords($formatter->format($cents)).' Sen':'');
        return $data;
    }
    protected function format(Kasbon $r,Request $request): array {
        return array_merge($r->only(['id','company_id','jumlah_dana','terbilang','informasi_transfer','keterangan','status','approval_level','rejected_note']),[
            'can_approve' => $r->isPending() && (
                ($request->user()->isSuperuser() && $r->isWaitingAtasan() && $r->isValidAtasan($request->user()))
                || ($request->user()->jabatan === Kasbon::LEVEL2_JABATAN && $r->isWaitingAdmin() && $r->user_id !== $request->user()->id)
            ),
            'tanggal'=>$r->tanggal?->format('Y-m-d'),'company_name'=>$r->company?->nama,
            'created_by'=>$r->user?->name,'has_attachment'=>(bool)$r->lampiran,
            'can_edit'=>$r->canBeEditedBy($request->user()),'can_cancel'=>$r->canBeCancelledBy($request->user()),
        ]);
    }
}
