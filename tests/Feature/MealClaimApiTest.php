<?php
namespace Tests\Feature;
use App\Enums\Role;
use App\Models\{User,Department,Company,MealClaim,EmployeeProfile};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MealClaimApiTest extends TestCase {
    use RefreshDatabase;
    protected function beforeRefreshingDatabase(): void {
        if(config('database.connections.'.config('database.default').'.database')!=='insys_db_testing') throw new \RuntimeException('Isolated test database required.');
    }
    private function employee(): User {
        $u=User::factory()->create(['level'=>Role::User]);
        $u->departments()->attach(Department::firstOrCreate(['nama_department'=>'Meals Test'])->id);
        return $u;
    }
    private function payload():array {
        return ['company_id'=>Company::firstOrCreate(['kode'=>'MEAL'],['nama'=>'Test'])->id,'claim_date'=>'2026-09-26',
            'items'=>[['meal_date'=>'2026-09-25','meal_type'=>'Lunch','merchant'=>'Cafe','amount'=>'15000.25','receipt'=>UploadedFile::fake()->image('receipt.jpg')]]];
    }
    public function test_owner_submission_totals_private_receipt_edit_and_cancel(): void {
        Storage::fake('private');$u=$this->employee();Sanctum::actingAs($u);
        $data=$this->payload()+['status'=>'Paid','total_amount'=>'1','user_id'=>999];
        $r=$this->post('/api/v1/meal-claims',$data,['Accept'=>'application/json'])->assertCreated()->assertJsonPath('data.status','Submitted')->assertJsonPath('data.total_amount','15000.25');
        $id=$r->json('data.id');$item=$r->json('data.items.0.id');
        $this->getJson('/api/v1/meal-claims')->assertJsonCount(1,'data');
        $this->getJson("/api/v1/meal-claims/$id/receipts/$item")->assertOk();
        $data['items'][0]['id']=$item;unset($data['items'][0]['receipt']);$data['items'][0]['amount']='20000.00';
        $this->postJson("/api/v1/meal-claims/$id",$data)->assertOk()->assertJsonPath('data.total_amount','20000.00');
        $model=MealClaim::find($id)->items()->first();
        $model->update(['ocr_status'=>'Completed','ocr_raw_text'=>'Old image']);
        $data['items'][0]['receipt']=UploadedFile::fake()->image('replacement.png');
        $this->post("/api/v1/meal-claims/$id",$data,['Accept'=>'application/json'])->assertOk();
        $this->assertSame('Not processed',$model->fresh()->ocr_status);
        $this->assertNull($model->fresh()->ocr_raw_text);
        unset($data['items'][0]['receipt']);
        $this->postJson("/api/v1/meal-claims/$id/cancel")->assertOk()->assertJsonPath('data.status','Cancelled');
        $this->postJson("/api/v1/meal-claims/$id",$data)->assertConflict();
        Sanctum::actingAs($this->employee());
        $this->getJson("/api/v1/meal-claims/$id/receipts/$item")->assertNotFound();
        $this->postJson("/api/v1/meal-claims/$id/cancel")->assertNotFound();
    }
    public function test_admin_verification_finance_approval_payment_and_replay(): void {
        Storage::fake('private');$owner=$this->employee();Sanctum::actingAs($owner);
        $id=$this->post('/api/v1/meal-claims',$this->payload(),['Accept'=>'application/json'])->assertCreated()->json('data.id');
        $admin=User::factory()->create(['level'=>Role::Admin]);Sanctum::actingAs($admin);
        $this->getJson('/api/v1/meal-claims?review=1')->assertJsonCount(1,'data');
        $this->postJson("/api/v1/meal-claims/$id/receive")->assertOk()->assertJsonPath('data.status','Receipt Received');
        $this->postJson("/api/v1/meal-claims/$id/receive")->assertConflict();
        $this->postJson("/api/v1/meal-claims/$id/verify",['note'=>'Checked'])->assertOk()->assertJsonPath('data.status','Verified');
        $this->postJson("/api/v1/meal-claims/$id/approve")->assertConflict();
        $finance=User::factory()->create(['level'=>Role::Superuser,'jabatan'=>'Finance Manager']);
        $finance->departments()->attach(Department::firstOrCreate(['nama_department'=>'Finance, Accounting & Tax Department'])->id);Sanctum::actingAs($finance);
        $this->postJson("/api/v1/meal-claims/$id/approve")->assertOk()->assertJsonPath('data.status','Approved');
        $this->postJson("/api/v1/meal-claims/$id/pay",['note'=>'Transfer ABC'])->assertOk()->assertJsonPath('data.status','Paid');
        $this->postJson("/api/v1/meal-claims/$id/pay")->assertConflict();
        $this->assertSame(4,MealClaim::find($id)->approvalHistories()->count());
    }
    public function test_validation_return_resubmission_and_foreign_receipt(): void {
        Storage::fake('private');$owner=$this->employee();Sanctum::actingAs($owner);
        $data=$this->payload();unset($data['items'][0]['receipt']);
        $this->postJson('/api/v1/meal-claims',$data)->assertUnprocessable();
        $data['items'][0]['receipt']=UploadedFile::fake()->create('bad.pdf',10,'application/pdf');
        $this->post('/api/v1/meal-claims',$data,['Accept'=>'application/json'])->assertUnprocessable();
        $created=$this->post('/api/v1/meal-claims',$this->payload(),['Accept'=>'application/json'])->assertCreated();
        $id=$created->json('data.id');$item=$created->json('data.items.0.id');
        $admin=User::factory()->create(['level'=>Role::Admin]);Sanctum::actingAs($admin);
        $this->postJson("/api/v1/meal-claims/$id/return",['note'=>' '])->assertUnprocessable();
        $this->postJson("/api/v1/meal-claims/$id/return",['note'=>'Correct merchant'])->assertOk()->assertJsonPath('data.status','Returned');
        Sanctum::actingAs($owner);unset($data['items'][0]['receipt']);$data['items'][0]['id']=$item+500;
        $this->postJson("/api/v1/meal-claims/$id",$data)->assertUnprocessable();
        $data['items'][0]['id']=$item;
        $this->postJson("/api/v1/meal-claims/$id",$data)->assertOk()->assertJsonPath('data.status','Submitted');
        $this->assertSame(2,MealClaim::find($id)->approvalHistories()->count());
    }
    public function test_visibility_matches_filament_and_no_self_approval(): void {
        $this->getJson('/api/v1/meal-claims')->assertUnauthorized();
        Storage::fake('private');$owner=$this->employee();Sanctum::actingAs($owner);
        $id=$this->post('/api/v1/meal-claims',$this->payload(),['Accept'=>'application/json'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/meal-claims/$id/verify")->assertConflict();
        $boss=User::factory()->create(['level'=>Role::Superuser]);
        Sanctum::actingAs($boss);$this->getJson('/api/v1/meal-claims?review=1')->assertJsonCount(0,'data');
        EmployeeProfile::create(['user_id'=>$owner->id,'atasan_id'=>$boss->id]);
        $this->getJson('/api/v1/meal-claims?review=1')->assertJsonCount(1,'data')->assertJsonPath('data.0.actions',[]);
    }
    public function test_finance_accounting_tax_member_can_review_all_claims_without_processing_them(): void {
        Storage::fake('private');$owner=$this->employee();Sanctum::actingAs($owner);
        $this->post('/api/v1/meal-claims',$this->payload(),['Accept'=>'application/json'])->assertCreated();
        $financeAccountingTax=User::factory()->create(['level'=>Role::User]);
        $financeAccountingTax->departments()->attach(Department::firstOrCreate(['nama_department'=>'Finance, Accounting & Tax Department'])->id);
        Sanctum::actingAs($financeAccountingTax);
        $this->getJson('/api/v1/meal-claims?review=1')->assertJsonCount(1,'data')->assertJsonPath('data.0.actions',[]);
    }
}
