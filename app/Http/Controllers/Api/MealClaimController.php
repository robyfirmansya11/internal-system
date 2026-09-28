<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{MealClaim, MealClaimItem, Company};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\{Rule, ValidationException};

class MealClaimController extends Controller
{
    private function visible(Request $request) {
        $user = $request->user();
        $query = MealClaim::query();
        if (!($user->isAdmin() || $user->isSuperadmin() || $user->isFinanceManager() || $user->isFinanceAccountingTaxMember())) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->isSuperuser()) $q->orWhereHas('user.profile', fn($p) => $p->where('atasan_id', $user->id));
            });
        }
        return $query;
    }
    public function companies() { return response()->json(Company::orderBy('nama')->get(['id','nama'])); }
    public function index(Request $request) {
        $q = $this->visible($request);
        if (!$request->boolean('review')) $q->where('user_id', $request->user()->id);
        else $q->where('user_id','!=',$request->user()->id);
        $rows = $q->with(['items','company','user'])->orderByDesc('id')->paginate(20);
        $rows->through(fn($r) => $this->format($r,$request));
        return response()->json($rows);
    }
    public function show(Request $request, int $id) {
        return response()->json(['data'=>$this->format($this->visible($request)->findOrFail($id),$request)]);
    }
    public function store(Request $request) { return $this->save($request); }
    public function update(Request $request, int $id) { return $this->save($request,$id); }
    private function save(Request $request, ?int $id=null) {
        $newPaths=[];
        try {
            $record=DB::transaction(function() use($request,$id,&$newPaths) {
                $user=$request->user();
                $record=$id ? MealClaim::where('user_id',$user->id)->lockForUpdate()->findOrFail($id) : null;
                if($record) abort_unless($record->canBeEditedBy($user),409,'This claim can no longer be edited.');
                $department=$user->departments()->first();
                abort_unless($department,422,'Your account is not assigned to a department.');
                $data=$request->validate([
                    'company_id'=>['required','integer',Rule::exists('companies','id')->whereNull('deleted_at')],
                    'claim_date'=>['required','date_format:Y-m-d'], 'employee_note'=>['nullable','string','max:5000'],
                    'items'=>['required','array','min:1','max:20'],
                    'items.*.id'=>['nullable','integer','distinct'],
                    'items.*.meal_date'=>['required','date_format:Y-m-d'],
                    'items.*.meal_type'=>['required',Rule::in(['Breakfast','Lunch','Dinner','Meal'])],
                    'items.*.merchant'=>['nullable','string','max:255'], 'items.*.note'=>['nullable','string','max:5000'],
                    'items.*.amount'=>['required','numeric','min:0','regex:/^\d{1,12}(\.\d{1,2})?$/'],
                    'items.*.receipt'=>['nullable','image','mimes:jpg,jpeg,png','max:5120'],
                ]);
                $total=0; $items=[];
                foreach($data['items'] as $i=>$input) {
                    $old=isset($input['id']) && $record ? $record->items()->find($input['id']) : null;
                    if(isset($input['id']) && !$old) throw ValidationException::withMessages(["items.$i.id"=>'This receipt does not belong to the claim.']);
                    $file=$request->file("items.$i.receipt");
                    if(!$file && !$old) throw ValidationException::withMessages(["items.$i.receipt"=>'A receipt photo is required.']);
                    $parts=explode('.',(string)$input['amount']);
                    $minor=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0'); $total+=$minor;
                    if($total>999999999999999) throw ValidationException::withMessages(['items'=>'The claim total is too large.']);
                    $path=$old?->receipt_path;
                    if($file) { $path=$file->store('meal-claims/receipts','private'); abort_unless($path,500,'Receipt upload failed.'); $newPaths[]=$path; }
                    $items[]=[$old, [
                        'meal_date'=>$input['meal_date'],'meal_type'=>$input['meal_type'],'merchant'=>$input['merchant']??null,
                        'note'=>$input['note']??null,'amount'=>intdiv($minor,100).'.'.str_pad((string)($minor%100),2,'0',STR_PAD_LEFT),
                        'receipt_path'=>$path,
                    ]];
                }
                $header=['company_id'=>$data['company_id'],'claim_date'=>$data['claim_date'],'employee_note'=>$data['employee_note']??null];
                if(!$record) $record=MealClaim::create($header+['user_id'=>$user->id,'department_id'=>$department->id,'status'=>'Submitted','approval_level'=>0]);
                else {
                    $before=$record->status;
                    $record->update($header+['status'=>'Submitted','approval_level'=>0]);
                    if($before==='Returned') $record->approvalHistories()->create(['user_id'=>$user->id,'action'=>'Resubmitted','status_before'=>$before,'status_after'=>'Submitted','approval_level'=>0]);
                }
                $keep=[];
                MealClaimItem::withoutEvents(function() use($record,$items,&$keep) {
                    foreach($items as [$old,$item]) {
                        if($old) {
                            if ($old->receipt_path !== $item['receipt_path']) $item += ['ocr_status'=>'Not processed','ocr_confidence'=>null,'ocr_raw_text'=>null];
                            $old->update($item); $keep[]=$old->id;
                        }
                        else $keep[]=$record->items()->create($item)->id;
                    }
                    $record->items()->whereNotIn('id',$keep)->delete();
                });
                $record->recalculateTotals();
                return $record->fresh();
            });
        } catch (\Throwable $e) { foreach($newPaths as $path) Storage::disk('private')->delete($path); throw $e; }
        return response()->json(['data'=>$this->format($record,$request)],$id?200:201);
    }
    private function actions(MealClaim $r, $u): array {
        $active=!in_array($r->status,['Approved','Paid','Rejected','Cancelled'],true);
        return array_keys(array_filter([
            'receive'=>$r->status==='Submitted' && $r->canBeManagedBy($u),
            'verify'=>in_array($r->status,['Submitted','Receipt Received'],true) && $r->canBeManagedBy($u),
            'approve'=>$r->canBeApprovedBy($u), 'pay'=>$r->canBePaidBy($u),
            'return'=>$active && $r->canBeManagedBy($u),
            'reject'=>$active && ($r->canBeManagedBy($u) || $r->canBeApprovedBy($u)),
            'cancel'=>$r->canBeCancelledBy($u),
        ]));
    }
    public function action(Request $request,int $id,string $action) {
        abort_unless(in_array($action,['receive','verify','approve','pay','return','reject','cancel'],true),404);
        return DB::transaction(function() use($request,$id,$action) {
            $r=$this->visible($request)->lockForUpdate()->findOrFail($id); $u=$request->user();
            abort_unless(in_array($action,$this->actions($r,$u),true),409,'This action is not available for this claim or account.');
            $data=$request->validate(['note'=>[in_array($action,['return','reject'])?'required':'nullable','string','max:5000']]);
            $note=$data['note']??null;
            $ok=match($action) {
                'receive'=>$r->receiveReceipts($u), 'verify'=>$r->verify($u,$note), 'approve'=>$r->approve($u),
                'pay'=>$r->markPaid($u,$note), 'return'=>$r->returnForCorrection($u,$note),
                'reject'=>$r->rejectClaim($u,$note), 'cancel'=>$r->cancelClaim($u),
            };
            abort_unless($ok,409,'The claim status has changed. Refresh and try again.');
            return response()->json(['data'=>$this->format($r->fresh(),$request)]);
        });
    }
    public function receipt(Request $request,int $id,int $item) {
        $r=$this->visible($request)->findOrFail($id);
        $receipt=$r->items()->findOrFail($item);
        abort_unless(Storage::disk('private')->exists($receipt->receipt_path),404);
        return Storage::disk('private')->response($receipt->receipt_path,null,['Cache-Control'=>'private, no-store']);
    }
    private function format(MealClaim $r,Request $request):array {
        $r->loadMissing(['items','company','user']);
        return $r->only(['id','company_id','status','total_amount','receipt_count','employee_note','verification_note','payment_note','rejected_note'])+[
            'claim_date'=>$r->claim_date->format('Y-m-d'),'company_name'=>$r->company?->nama,'created_by'=>$r->user?->name,
            'can_edit'=>$r->canBeEditedBy($request->user()),'actions'=>$this->actions($r,$request->user()),
            'items'=>$r->items->map(fn($i)=>$i->only(['id','meal_type','merchant','amount','note'])+['meal_date'=>$i->meal_date->format('Y-m-d')])->values(),
        ];
    }
}
