<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PermohonanStempel;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StampApplicationController extends Controller {
    public function companies() { return response()->json(Company::orderBy('nama')->get(['id','nama'])); }
    public function index(Request $request) {
        $records=PermohonanStempel::where('user_id',$request->user()->id)->with(['company','user'])->orderByDesc('created_at')->orderByDesc('id')->paginate(20);
        $records->through(fn($r)=>$this->format($r,$request));
        return response()->json($records);
    }
    public function show(Request $request,int $id) {
        return response()->json($this->format(PermohonanStempel::where('user_id',$request->user()->id)->findOrFail($id),$request));
    }
    public function store(Request $request) {
        $data=$this->validated($request);
        $user=$request->user(); $department=$user->departments()->first();
        abort_unless($department,422,'Akun belum memiliki department.');
        $boss=$user->profile?->atasan;
        $finance=$user->jabatan===PermohonanStempel::LEVEL2_JABATAN;
        $manager=$user->jabatan==='Manager';
        $auto=$finance || ($manager && (!$boss || $boss->id===$user->id));
        if (!$auto && (!$boss || $boss->id===$user->id)) {
            abort(422,'Akun belum memiliki atasan langsung yang valid. Hubungi HR atau IT.');
        }
        $path=$request->file('lampiran')->store('permohonan-stempel','public');
        abort_unless($path,500,'Lampiran gagal disimpan.');
        try {
            $record=DB::transaction(function() use($data,$user,$department,$boss,$auto,$path) {
                $record=PermohonanStempel::create(array_merge($data,[
                    'user_id'=>$user->id,'department_id'=>$department->id,'lampiran'=>$path,
                    'status'=>$auto?'Approved':'Pending Approval','approval_level'=>$auto?3:1,
                    'approved_by_manager'=>$auto?$user->id:null,'approved_manager_at'=>$auto?now():null,
                    'approved_by'=>$auto?$user->id:null,'approved_at'=>$auto?now():null,
                ]));
                if (!$auto) Notification::make()->title('New Stamp Application Letter')
                    ->body("{$user->name} submitted a stamp application request.")
                    ->icon('heroicon-o-document-text')->sendToDatabase($boss);
                return $record;
            });
        } catch (\Throwable $e) { Storage::disk('public')->delete($path); throw $e; }
        return response()->json(['data'=>$this->format($record,$request)],201);
    }
    public function update(Request $request,int $id) {
        $path=null;
        try {
            $record=DB::transaction(function() use($request,$id,&$path) {
                $record=PermohonanStempel::where('user_id',$request->user()->id)->lockForUpdate()->findOrFail($id);
                abort_unless($record->canBeEditedBy($request->user()),422,'Permohonan tidak dapat diedit pada status ini.');
                $data=$this->validated($request,$record); unset($data['lampiran']);
                if ($request->hasFile('lampiran')) {
                    $path=$request->file('lampiran')->store('permohonan-stempel','public');
                    abort_unless($path,500,'Lampiran gagal disimpan.'); $data['lampiran']=$path;
                }
                $record->update($data); return $record;
            });
        } catch (\Throwable $e) { if($path) Storage::disk('public')->delete($path); throw $e; }
        return response()->json(['data'=>$this->format($record,$request)]);
    }
    public function cancel(Request $request,int $id) {
        return DB::transaction(function() use($request,$id) {
            $record=PermohonanStempel::where('user_id',$request->user()->id)->lockForUpdate()->findOrFail($id);
            abort_unless($record->cancel($request->user()),422,'Permohonan tidak dapat dibatalkan.');
            return response()->json(['data'=>$this->format($record,$request)]);
        });
    }
    private function validated(Request $request,?PermohonanStempel $record=null): array {
        return $request->validate([
            'company_id'=>['required','integer',Rule::exists('companies','id')->whereNull('deleted_at')],
            'tanggal'=>['required','date_format:Y-m-d'],
            'nomor_surat'=>['required','string','max:255'], 'tujuan'=>['required','string','max:255'],
            'ditandatangani_oleh'=>['required','string','max:255'], 'keterangan'=>['nullable','string','max:5000'],
            'tanggal_surat'=>['nullable','date_format:Y-m-d'],'tanggal_stempel'=>['nullable','date_format:Y-m-d'],
            'lampiran'=>[$record && $record->lampiran?'nullable':'required','file','mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx','max:10240'],
        ]);
    }
    protected function format(PermohonanStempel $r,Request $request): array {
        return array_merge($r->only(['id','company_id','nomor_surat','tujuan','ditandatangani_oleh','keterangan','status','approval_level','rejected_note']),[
            'can_approve' => $r->isPending() && (
                ($request->user()->canApprove() && $r->isWaitingAtasan() && $r->isValidAtasan($request->user()))
                || ($request->user()->jabatan === PermohonanStempel::LEVEL2_JABATAN && $r->isWaitingAdmin() && $r->user_id !== $request->user()->id)
            ),
            'tanggal'=>$r->tanggal?->format('Y-m-d'),'tanggal_surat'=>$r->tanggal_surat?->format('Y-m-d'),
            'tanggal_stempel'=>$r->tanggal_stempel?->format('Y-m-d'),'company_name'=>$r->company?->nama,
            'created_by'=>$r->user?->name,'has_attachment'=>(bool)$r->lampiran,
            'can_edit'=>$r->canBeEditedBy($request->user()),'can_cancel'=>$r->canBeCancelledBy($request->user()),
        ]);
    }
}
