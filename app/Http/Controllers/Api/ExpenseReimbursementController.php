<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\NotaPenggantianBiaya;
use App\Models\NotaPenggantianBiayaDetail;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExpenseReimbursementController extends Controller {
    public function companies() { return response()->json(Company::orderBy('nama')->get(['id','nama'])); }
    public function index(Request $request) {
        $records=NotaPenggantianBiaya::where('user_id',$request->user()->id)->with(['company','user','details'])->orderByDesc('created_at')->orderByDesc('id')->paginate(20);
        $records->through(fn($r)=>$this->format($r,$request));
        return response()->json($records);
    }
    public function show(Request $request,int $id) {
        return response()->json($this->format(NotaPenggantianBiaya::where('user_id',$request->user()->id)->findOrFail($id),$request));
    }
    public function store(Request $request) {
        [$data,$details]=$this->validated($request);
        $user=$request->user(); $department=$user->departments()->first();
        abort_unless($department,422,'Akun belum memiliki department.');
        $boss=$user->profile?->atasan;
        $finance=$user->jabatan===NotaPenggantianBiaya::LEVEL2_JABATAN;
        $manager=$user->isSuperuser();
        $skip=!$boss || $boss->id===$user->id;
        $level=$finance?3:(($manager || $skip)?2:1);
        $selfManager=$finance;
        $path=$request->hasFile('lampiran')?$request->file('lampiran')->store('nota-penggantian-biaya','public'):null;
        if($request->hasFile('lampiran')) abort_unless($path,500,'Lampiran gagal disimpan.');
        try {
            $record=DB::transaction(function() use($data,$details,$user,$department,$boss,$finance,$level,$selfManager,$path) {
                $record=NotaPenggantianBiaya::create(array_merge($data,[
                    'user_id'=>$user->id,'department_id'=>$department->id,'lampiran'=>$path,
                    'status'=>$finance?'Approved':'Pending Approval','approval_level'=>$level,
                    'approved_by_manager'=>$selfManager?$user->id:null,'approved_manager_at'=>$selfManager?now():null,
                    'approved_by'=>$finance?$user->id:null,'approved_at'=>$finance?now():null,
                ]));
                NotaPenggantianBiayaDetail::withoutEvents(fn()=>$record->details()->createMany($details));
                $recipients=$finance?collect([$user]):($level===1?collect([$boss]):User::where('jabatan',NotaPenggantianBiaya::LEVEL2_JABATAN)->get());
                foreach($recipients as $recipient) Notification::make()->title($finance?'NotaPenggantianBiaya Disetujui Otomatis':'Pengajuan NotaPenggantianBiaya Baru')
                    ->body("{$user->name} mengajukan penggantian biaya.")->icon('heroicon-o-banknotes')->sendToDatabase($recipient);
                return $record;
            });
        } catch (\Throwable $e) { if($path) Storage::disk('public')->delete($path); throw $e; }
        return response()->json(['data'=>$this->format($record,$request)],201);
    }
    public function update(Request $request,int $id) {
        $path=null;
        try {
            $record=DB::transaction(function() use($request,$id,&$path) {
                $record=NotaPenggantianBiaya::where('user_id',$request->user()->id)->lockForUpdate()->findOrFail($id);
                abort_unless($record->canBeEditedBy($request->user()),422,'Permohonan tidak dapat diedit pada status ini.');
                [$data,$details]=$this->validated($request); unset($data['lampiran']);
                if ($request->hasFile('lampiran')) {
                    $path=$request->file('lampiran')->store('nota-penggantian-biaya','public');
                    abort_unless($path,500,'Lampiran gagal disimpan.'); $data['lampiran']=$path;
                }
                $record->update($data);
                $record->details()->delete();
                NotaPenggantianBiayaDetail::withoutEvents(fn()=>$record->details()->createMany($details));
                return $record;
            });
        } catch (\Throwable $e) { if($path) Storage::disk('public')->delete($path); throw $e; }
        return response()->json(['data'=>$this->format($record,$request)]);
    }
    public function cancel(Request $request,int $id) {
        return DB::transaction(function() use($request,$id) {
            $record=NotaPenggantianBiaya::where('user_id',$request->user()->id)->lockForUpdate()->findOrFail($id);
            abort_unless($record->cancel($request->user()),422,'Permohonan tidak dapat dibatalkan.');
            return response()->json(['data'=>$this->format($record,$request)]);
        });
    }
    private function validated(Request $request):array {
        $data=$request->validate([
            'company_id'=>['required','integer',Rule::exists('companies','id')->whereNull('deleted_at')],
            'tanggal'=>['required','date_format:Y-m-d'],'informasi_transfer'=>['nullable','string','max:5000'],
            'jumlah_lampiran'=>['required','integer','min:0','max:9999'],
            'details'=>['required','array','min:1','max:5'],
            'details.*.keterangan'=>['required','string','max:5000'],
            'details.*.jumlah'=>['required','numeric','min:0','max:999999999999.99','regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'lampiran'=>['nullable','file','mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx','max:10240'],
        ]);
        $total=0;$details=[];
        foreach($data['details'] as $item){
            $parts=explode('.',(string)$item['jumlah']);$minor=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
            $total+=$minor;
            if($total>99999999999999)throw \Illuminate\Validation\ValidationException::withMessages(['details'=>'Total maksimal Rp 999.999.999.999,99.']);
            $details[]=['keterangan'=>$item['keterangan'],'jumlah'=>$this->decimal($minor)];
        }
        $formatter=new \NumberFormatter('en',\NumberFormatter::SPELLOUT);
        $words=ucwords($formatter->format(intdiv($total,100))).' Rupiah'.($total%100?' and '.ucwords($formatter->format($total%100)).' Sen':'');
        $header=\Illuminate\Support\Arr::only($data,['company_id','tanggal','informasi_transfer','jumlah_lampiran']);
        return [array_merge($header,['keterangan'=>'','jumlah'=>$this->decimal($total),'jumlah_total'=>$this->decimal($total),'terbilang'=>$words]),$details];
    }
    private function decimal(int $minor):string{return intdiv($minor,100).'.'.str_pad((string)($minor%100),2,'0',STR_PAD_LEFT);}
    protected function format(NotaPenggantianBiaya $r,Request $request): array {
        return array_merge($r->only(['id','company_id','jumlah_total','jumlah_lampiran','terbilang','informasi_transfer','keterangan','status','approval_level','rejected_note']),[
            'can_approve' => $r->isPending() && (
                ($request->user()->isSuperuser() && $r->isWaitingAtasan() && $r->isValidAtasan($request->user()))
                || ($request->user()->jabatan === NotaPenggantianBiaya::LEVEL2_JABATAN && $r->isWaitingAdmin() && $r->user_id !== $request->user()->id)
            ),
            'tanggal'=>$r->tanggal?->format('Y-m-d'),'company_name'=>$r->company?->nama,
            'details'=>$r->details->map(fn($d)=>$d->only(['keterangan','jumlah']))->values(),
            'created_by'=>$r->user?->name,'has_attachment'=>(bool)$r->lampiran,
            'can_edit'=>$r->canBeEditedBy($request->user()),'can_cancel'=>$r->canBeCancelledBy($request->user()),
        ]);
    }
}
