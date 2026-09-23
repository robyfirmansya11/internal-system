<?php
namespace Tests\Feature;

use App\Enums\Role;
use App\Models\{User, EmployeeProfile, Department, FormCuti, KuotaCuti};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaveApprovalApiTest extends TestCase
{
    use RefreshDatabase;
    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.connections.'.config('database.default').'.database') !== 'insys_db_testing') {
            throw new \RuntimeException('Tests require isolated insys_db_testing database.');
        }
    }
    private function actor(Role $role, string $job): User
    {
        return User::withoutEvents(fn () => User::factory()->create(['level' => $role, 'jabatan' => $job]));
    }
    private function leave(User $manager, int $stage = 1): FormCuti
    {
        $user = $this->actor(Role::User, 'Staff');
        EmployeeProfile::create(['user_id' => $user->id, 'atasan_id' => $manager->id]);
        KuotaCuti::create(['user_id' => $user->id, 'tahun' => now()->year, 'kuota_tahunan' => 12, 'cuti_terpakai' => 0]);
        return FormCuti::create([
            'user_id' => $user->id, 'department_id' => Department::firstOrCreate(['nama_department' => 'Test'])->id,
            'tahun' => now()->year, 'tanggal_mulai' => today(), 'tanggal_selesai' => today()->addDay(),
            'jumlah_hari' => 2, 'jenis_cuti' => 'Cuti Tahunan', 'alasan' => 'Family event',
            'status' => 'Pending Approval', 'approval_level' => $stage,
        ]);
    }
    public function test_manager_then_hr_and_quota_is_deducted_once(): void
    {
        $manager = $this->actor(Role::Superuser, 'Manager');
        $hr = $this->actor(Role::Admin, 'HRD');
        $record = $this->leave($manager);
        $this->leave($this->actor(Role::Superuser, 'Manager'));
        Sanctum::actingAs($manager);
        $this->getJson('/api/v1/cuti/approvals')->assertOk()->assertJsonCount(1)->assertJsonPath('0.can_approve', true);
        $url = '/api/v1/cuti/'.$record->id.'/approve';
        $this->postJson($url)->assertOk();
        $this->assertSame(3, $record->fresh()->approval_level);
        $this->assertEquals(0, $record->kuota()->first()->cuti_terpakai);
        $this->postJson($url)->assertForbidden();
        Sanctum::actingAs($hr);
        $this->postJson($url)->assertOk();
        $this->postJson($url)->assertConflict();
        $this->assertSame('Approved', $record->fresh()->status);
        $this->assertEquals(2, $record->kuota()->first()->cuti_terpakai);
    }
    public function test_finance_stage_and_read_only_superadmin_permissions(): void
    {
        $manager = $this->actor(Role::Superuser, 'Manager');
        $finance = $this->actor(Role::Admin, 'Finance Manager');
        $record = $this->leave($manager, 2);
        Sanctum::actingAs($this->actor(Role::Superadmin, 'IT'));
        $this->getJson('/api/v1/cuti/approvals')->assertOk()->assertJsonPath('0.can_approve', false)->assertJsonPath('0.can_reject', false);
        $this->postJson('/api/v1/cuti/'.$record->id.'/approve')->assertForbidden();
        Sanctum::actingAs($finance);
        $this->getJson('/api/v1/cuti/approvals')->assertJsonPath('0.can_approve', true);
        $this->postJson('/api/v1/cuti/'.$record->id.'/approve')->assertOk();
        $this->assertSame(3, $record->fresh()->approval_level);
        $this->assertSame($finance->id, $record->fresh()->approved_by_finance_manager);
    }
    public function test_auth_rejection_and_cancelled_records(): void
    {
        $manager = $this->actor(Role::Superuser, 'Manager');
        $record = $this->leave($manager);
        $url = '/api/v1/cuti/'.$record->id;
        $this->getJson('/api/v1/cuti/approvals')->assertUnauthorized();
        Sanctum::actingAs($this->actor(Role::User, 'Staff'));
        $this->getJson('/api/v1/cuti/approvals')->assertForbidden();
        $this->postJson($url.'/approve')->assertForbidden();
        $this->postJson($url.'/reject', ['rejected_note' => 'No'])->assertForbidden();
        Sanctum::actingAs($manager);
        $this->postJson($url.'/reject', ['rejected_note' => ' '])->assertUnprocessable();
        $this->postJson($url.'/reject', ['rejected_note' => str_repeat('x', 501)])->assertUnprocessable();
        $this->postJson($url.'/reject', ['rejected_note' => 'Please reschedule'])->assertOk();
        $this->assertSame('Please reschedule', $record->fresh()->rejected_note);
        $this->postJson($url.'/approve')->assertConflict();
        $cancelled = $this->leave($manager);
        $cancelled->update(['status' => 'Cancelled']);
        $this->postJson('/api/v1/cuti/'.$cancelled->id.'/approve')->assertConflict();
        $this->assertEquals(0, $record->kuota()->first()->cuti_terpakai);
    }
    public function test_insufficient_quota_rolls_back_final_approval(): void
    {
        $record = $this->leave($this->actor(Role::Superuser, 'Manager'), 3);
        $record->kuota()->first()->update(['cuti_terpakai' => 11]);
        Sanctum::actingAs($this->actor(Role::Admin, 'HRD'));
        $this->postJson('/api/v1/cuti/'.$record->id.'/approve')->assertUnprocessable();
        $this->assertSame('Pending Approval', $record->fresh()->status);
        $this->assertNull($record->fresh()->approved_by);
        $this->assertEquals(11, $record->kuota()->first()->cuti_terpakai);
    }
}
