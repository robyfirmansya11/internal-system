<?php
namespace Tests\Feature;
use App\Enums\Role;
use App\Models\{User, Department, Company, EmployeeProfile, PermohonanStempel};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
class StampApprovalApiTest extends TestCase {
    use RefreshDatabase;
    protected function beforeRefreshingDatabase(): void {
        if(config('database.connections.'.config('database.default').'.database') !== 'insys_db_testing') throw new \RuntimeException('Isolated test database required.');
    }
    private function record(User $boss): PermohonanStempel {
        $user=User::factory()->create(['level'=>Role::User]);
        EmployeeProfile::create(['user_id'=>$user->id,'atasan_id'=>$boss->id]);
        return PermohonanStempel::create([
            'user_id'=>$user->id,'department_id'=>Department::firstOrCreate(['nama_department'=>'Test'])->id,
            'company_id'=>Company::firstOrCreate(['kode'=>'STAPP'],['nama'=>'Test'])->id,
            'tanggal'=>today(),'nomor_surat'=>'STAMP-001','tujuan'=>'Client','ditandatangani_oleh'=>'Director','lampiran'=>'test.pdf',
            'status'=>'Pending Approval','approval_level'=>1,
        ]);
    }
    public function test_direct_supervisor_approves_finally_and_cannot_replay(): void {
        foreach([Role::Superuser,Role::Admin] as $role){
            $boss=User::factory()->create(['level'=>$role]);$r=$this->record($boss);
            $this->record(User::factory()->create(['level'=>Role::Superuser]));
            Sanctum::actingAs($boss);
            $this->getJson('/api/v1/stamp-applications/approvals')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.can_approve',true);
            $this->postJson('/api/v1/stamp-applications/'.$r->id.'/approve')->assertOk()->assertJsonPath('data.status','Approved')->assertJsonPath('data.approval_level',2)->assertJsonPath('data.can_approve',false);
            $this->assertSame($boss->id,$r->fresh()->approved_by);
            $this->assertNotNull($r->fresh()->approved_at);
            $this->assertSame(1,$r->approvalHistories()->count());
            $this->postJson('/api/v1/stamp-applications/'.$r->id.'/approve')->assertConflict();
            $this->getJson('/api/v1/stamp-applications/approvals')->assertJsonCount(0,'data');
        }
    }
    public function test_auth_and_unrelated_supervisor_cannot_decide(): void {
        $boss=User::factory()->create(['level'=>Role::Superuser]);$r=$this->record($boss);
        $this->getJson('/api/v1/stamp-applications/approvals')->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create(['level'=>Role::User]));
        $this->getJson('/api/v1/stamp-applications/approvals')->assertForbidden();
        $this->postJson('/api/v1/stamp-applications/'.$r->id.'/approve')->assertForbidden();
        Sanctum::actingAs(User::factory()->create(['level'=>Role::Superuser]));
        $this->getJson('/api/v1/stamp-applications/approvals')->assertJsonCount(0,'data');
        $this->postJson('/api/v1/stamp-applications/'.$r->id.'/reject',['rejected_note'=>'No'])->assertForbidden();
    }
    public function test_rejection_validation_and_cancelled_records(): void {
        $boss=User::factory()->create(['level'=>Role::Superuser]);$r=$this->record($boss);Sanctum::actingAs($boss);
        $url='/api/v1/stamp-applications/'.$r->id;
        foreach([' ',str_repeat('x',5001)] as $note) $this->postJson($url.'/reject',['rejected_note'=>$note])->assertUnprocessable();
        $this->postJson($url.'/reject',['rejected_note'=>'Incorrect signatory'])->assertOk()->assertJsonPath('data.rejected_note','Incorrect signatory');
        $this->assertNotNull($r->fresh()->rejected_at);
        $this->postJson($url.'/approve')->assertConflict();
        $r=$this->record($boss);$r->update(['status'=>'Cancelled']);
        $this->postJson('/api/v1/stamp-applications/'.$r->id.'/approve')->assertConflict();
    }
}
