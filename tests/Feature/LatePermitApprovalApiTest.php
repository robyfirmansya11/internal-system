<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Keterlambatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LatePermitApprovalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.connections.'.config('database.default').'.database') !== 'insys_db_testing') {
            throw new \RuntimeException('Tests require isolated insys_db_testing database.');
        }
    }

    private function permit(User $manager): Keterlambatan
    {
        $employee = User::factory()->create(['level' => Role::User]);
        EmployeeProfile::create(['user_id' => $employee->id, 'atasan_id' => $manager->id]);
        $department = Department::firstOrCreate(['nama_department' => 'Approval Test']);
        return Keterlambatan::create([
            'user_id' => $employee->id, 'department_id' => $department->id,
            'tanggal' => today(), 'jam_masuk' => '09:15', 'alasan' => 'Transport delay',
            'status' => 'Pending Approval', 'approval_level' => 1,
        ]);
    }

    public function test_manager_queue_contains_only_direct_reports_at_manager_stage(): void
    {
        $manager = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Manager']);
        $own = $this->permit($manager);
        $this->permit(User::factory()->create(['level' => Role::Superuser]));
        $this->permit($manager)->update(['approval_level' => 2]);
        $this->permit($manager)->update(['status' => 'Cancelled']);
        Sanctum::actingAs($manager);
        $this->getJson('/api/v1/late-working-permits/approvals')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id)
            ->assertJsonPath('data.0.can_approve', true)
            ->assertJsonPath('data.0.employee_name', $own->user->name);
        $this->getJson('/api/v1/late-working-permits')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_manager_then_hr_approval_records_audit_and_prevents_replay(): void
    {
        $manager = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Manager']);
        $hr = User::factory()->create(['level' => Role::Admin, 'jabatan' => 'HRD']);
        $record = $this->permit($manager);
        $url = '/api/v1/late-working-permits/'.$record->id;
        Sanctum::actingAs($hr);
        $this->postJson($url.'/approve')->assertConflict();
        Sanctum::actingAs($manager);
        $this->postJson($url.'/approve')->assertOk()->assertJsonPath('data.approval_level', 2)
            ->assertJsonPath('data.status', 'Pending Approval')->assertJsonPath('data.can_approve', false);
        $this->postJson($url.'/approve')->assertConflict();
        $this->getJson('/api/v1/late-working-permits/approvals')->assertJsonCount(0, 'data');
        $this->assertSame($manager->id, $record->fresh()->approved_by_manager);
        Sanctum::actingAs($hr);
        $this->getJson('/api/v1/late-working-permits/approvals')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson($url.'/approve')->assertOk()->assertJsonPath('data.status', 'Approved');
        $this->postJson($url.'/approve')->assertConflict();
        $this->assertSame($hr->id, $record->fresh()->approved_by);
        $this->assertSame(2, $record->approvalHistories()->count());
    }

    public function test_unauthorized_users_cannot_list_or_decide(): void
    {
        $manager = User::factory()->create(['level' => Role::Superuser]);
        $record = $this->permit($manager);
        $url = '/api/v1/late-working-permits/'.$record->id;
        $this->getJson('/api/v1/late-working-permits/approvals')->assertUnauthorized();
        $this->postJson($url.'/approve')->assertUnauthorized();
        foreach ([Role::User, Role::Admin, Role::Superadmin] as $role) {
            Sanctum::actingAs(User::withoutEvents(fn () => User::factory()->create(['level' => $role, 'jabatan' => 'Staff'])));
            $this->getJson('/api/v1/late-working-permits/approvals')->assertForbidden();
            $this->postJson($url.'/approve')->assertForbidden();
            $this->postJson($url.'/reject', ['rejected_note' => 'No'])->assertForbidden();
        }
        Sanctum::actingAs(User::factory()->create(['level' => Role::Superuser]));
        $this->postJson($url.'/approve')->assertForbidden();
        $this->assertSame(1, $record->fresh()->approval_level);
    }

    public function test_rejection_requires_reason_and_terminal_records_cannot_be_approved(): void
    {
        $manager = User::factory()->create(['level' => Role::Superuser]);
        Sanctum::actingAs($manager);
        $record = $this->permit($manager);
        $url = '/api/v1/late-working-permits/'.$record->id;
        foreach (['', '   ', str_repeat('a', 5001), ['invalid']] as $note) {
            $this->postJson($url.'/reject', ['rejected_note' => $note])->assertUnprocessable();
        }
        $this->postJson($url.'/reject', ['rejected_note' => 'Insufficient explanation'])->assertOk()
            ->assertJsonPath('data.status', 'Rejected')->assertJsonPath('data.rejected_note', 'Insufficient explanation');
        $this->postJson($url.'/approve')->assertConflict();
        $this->assertSame(1, $record->approvalHistories()->count());
        $cancelled = $this->permit($manager);
        $cancelled->update(['status' => 'Cancelled']);
        $this->postJson('/api/v1/late-working-permits/'.$cancelled->id.'/approve')->assertConflict();
        $this->assertSame('Cancelled', $cancelled->fresh()->status);
    }
}
