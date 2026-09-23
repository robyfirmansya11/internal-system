<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PerjalananDinas;
use App\Models\PerjalananDinasDetail;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TravelReimbursementController extends Controller {
    private const COSTS=['amount_transportasi','amount_tunjangan','amount_hotel','misc','amount_other'];
    public function companies(){return response()->json(Company::orderBy('nama')->get(['id','nama']));}
    public function index(Request $request){
        $records=PerjalananDinas::where('user_id',$request->user()->id)->with(['company','user','details'])->orderByDesc('created_at')->orderByDesc('id')->paginate(20);
        $records->through(fn($r)=>$this->format($r,$request));return response()->json($records);
    }
    public function show(Request $request,int $id){return response()->json($this->format(PerjalananDinas::where('user_id',$request->user()->id)->findOrFail($id),$request));}
    public function store(Request $request){
        [$header,$details]=$this->validated($request);
        $user=$request->user();$department=$user->departments()->first();abort_unless($department,422,'Akun belum memiliki department.');
        $boss=$user->profile?->atasan;$finance=$user->jabatan===PerjalananDinas::LEVEL2_JABATAN;
        // Match the executing Filament create flow: any non-FM with a boss waits at level 1.
        $level=$finance?3:(($boss && $boss->id!==$user->id)?1:2);
        $record=DB::transaction(function()use($header,$details,$user,$department,$boss,$finance,$level){
            $record=PerjalananDinas::create(array_merge($header,[
                'user_id'=>$user->id,'department_id'=>$department->id,'status'=>$finance?'Approved':'Pending Approval','approval_level'=>$level,
                'approved_by_manager'=>$finance?$user->id:null,'approved_manager_at'=>$finance?now():null,
                'approved_by'=>$finance?$user->id:null,'approved_at'=>$finance?now():null,
            ]));
            // Subtotals are already calculated in integer cents; avoid float-based model callbacks.
            PerjalananDinasDetail::withoutEvents(fn()=>$record->details()->createMany($details));
            $recipients=$finance?collect([$user]):($level===1?collect([$boss]):User::where('jabatan',PerjalananDinas::LEVEL2_JABATAN)->get());
            foreach($recipients as $recipient)Notification::make()->title($finance?'Travel Reimbursement Approved':'New Travel Reimbursement Request')
                ->body("{$user->name} submitted a travel reimbursement request.")->icon('heroicon-o-briefcase')->sendToDatabase($recipient);
            return $record;
        });
        return response()->json(['data'=>$this->format($record,$request)],201);
    }
    public function update(Request $request,int $id){
        $record=DB::transaction(function()use($request,$id){
            $r=PerjalananDinas::where('user_id',$request->user()->id)->lockForUpdate()->findOrFail($id);
            abort_unless($r->canBeEditedBy($request->user()),422,'Pengajuan tidak dapat diedit pada status ini.');
            [$header,$details]=$this->validated($request);
            $r->update($header);
            $r->details()->delete();
            PerjalananDinasDetail::withoutEvents(fn()=>$r->details()->createMany($details));
            return $r;
        });return response()->json(['data'=>$this->format($record,$request)]);
    }
    public function cancel(Request $request,int $id){return DB::transaction(function()use($request,$id){
        $r=PerjalananDinas::where('user_id',$request->user()->id)->lockForUpdate()->findOrFail($id);
        abort_unless($r->cancel($request->user()),422,'Pengajuan tidak dapat dibatalkan.');return response()->json(['data'=>$this->format($r,$request)]);
    });}
    private function validated(Request $request):array {
        $rules=[
            'company_id'=>['required','integer',Rule::exists('companies','id')->whereNull('deleted_at')],
            'keterangan'=>['required','string','max:5000'],'catatan'=>['nullable','string','max:5000'],
            'jumlah_lampiran'=>['required','integer','min:0','max:9999'],'details'=>['required','array','min:1','max:5'],
            'details.*.tanggal_berangkat'=>['required','date_format:Y-m-d'],
            'details.*.tanggal_tujuan'=>['required','date_format:Y-m-d'],
            'details.*.waktu_berangkat'=>['nullable','date_format:H:i'],'details.*.waktu_tujuan'=>['nullable','date_format:H:i'],
            'details.*.tempat_berangkat'=>['required','string','max:255'],'details.*.tempat_tujuan'=>['required','string','max:255'],
            'details.*.jumlah_hari'=>['required','integer','min:1','max:3650'],'details.*.lama_hotel'=>['required','integer','min:0','max:3650'],
        ];
        foreach(self::COSTS as $key)$rules['details.*.'.$key]=['required','numeric','min:0','max:999999999.99','regex:/^\d{1,9}(\.\d{1,2})?$/'];
        $data=$request->validate($rules);$total=0;$details=[];
        foreach($data['details'] as $i=>$row){
            if($row['tanggal_tujuan']<$row['tanggal_berangkat'] || ($row['tanggal_tujuan']===$row['tanggal_berangkat'] && !empty($row['waktu_berangkat']) && !empty($row['waktu_tujuan']) && $row['waktu_tujuan']<$row['waktu_berangkat'])){
                throw ValidationException::withMessages(["details.$i.tanggal_tujuan"=>'Waktu tiba tidak boleh sebelum keberangkatan.']);
            }
            $cost=[];foreach(self::COSTS as $key){$p=explode('.',(string)$row[$key]);$cost[$key]=(int)$p[0]*100+(int)str_pad($p[1]??'',2,'0');}
            $subtotal=$cost['amount_transportasi']+$cost['amount_tunjangan']*(int)$row['jumlah_hari']+$cost['amount_hotel']*(int)$row['lama_hotel']+$cost['misc']+$cost['amount_other'];
            $total+=$subtotal;
            if($total>99999999999999)throw ValidationException::withMessages(['details'=>'Total maksimal Rp 999.999.999.999,99.']);
            // Whitelist nested fields; ignore supplied IDs, owner IDs and subtotals.
            $clean=\Illuminate\Support\Arr::only($row,['tanggal_berangkat','tanggal_tujuan','waktu_berangkat','waktu_tujuan','tempat_berangkat','tempat_tujuan','jumlah_hari','lama_hotel']);
            foreach($cost as $key=>$value)$clean[$key]=$this->decimal($value);
            $clean['subtotal']=$this->decimal($subtotal);$details[]=$clean;
        }
        $formatter=new \NumberFormatter('en',\NumberFormatter::SPELLOUT);
        $words=ucwords($formatter->format(intdiv($total,100))).' Rupiah'.($total%100?' and '.ucwords($formatter->format($total%100)).' Sen':'');
        return [array_merge(\Illuminate\Support\Arr::only($data,['company_id','keterangan','catatan','jumlah_lampiran']),['total'=>$this->decimal($total),'terbilang'=>$words]),$details];
    }
    private function decimal(int $value):string{return intdiv($value,100).'.'.str_pad((string)($value%100),2,'0',STR_PAD_LEFT);}
    protected function format(PerjalananDinas $r,Request $request):array{
        return array_merge($r->only(['id','company_id','keterangan','jumlah_lampiran','catatan','total','terbilang','status','approval_level','rejected_note']),[
            'can_approve' => $r->isPending() && (
                ($request->user()->isSuperuser() && $r->isWaitingAtasan() && $r->isValidAtasan($request->user()))
                || ($request->user()->jabatan === PerjalananDinas::LEVEL2_JABATAN && $r->isWaitingAdmin() && $r->user_id !== $request->user()->id)
            ),
            'company_name'=>$r->company?->nama,'created_by'=>$r->user?->name,'can_edit'=>$r->canBeEditedBy($request->user()),'can_cancel'=>$r->canBeCancelledBy($request->user()),
            'details'=>$r->details->map(fn($d)=>array_merge($d->only(['tempat_berangkat','tempat_tujuan','jumlah_hari','lama_hotel','amount_transportasi','amount_tunjangan','amount_hotel','misc','amount_other','subtotal']),[
                'tanggal_berangkat'=>$d->tanggal_berangkat?->format('Y-m-d'),'tanggal_tujuan'=>$d->tanggal_tujuan?->format('Y-m-d'),
                'waktu_berangkat'=>$d->waktu_berangkat?substr($d->waktu_berangkat,0,5):null,'waktu_tujuan'=>$d->waktu_tujuan?substr($d->waktu_tujuan,0,5):null,
            ]))->values(),
        ]);
    }
}
