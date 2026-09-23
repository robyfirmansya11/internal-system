<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\SuratPerintahBayar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentApprovalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.connections.'.config('database.default').'.database') !== 'insys_db_testing') {
            throw new \RuntimeException('Tests require isolated insys_db_testing database.');
        }
    }

    private function permit(User $manager): SuratPerintahBayar
    {
        $employee = User::factory()->create(['level' => Role::User]);
        EmployeeProfile::create(['user_id' => $employee->id, 'atasan_id' => $manager->id]);
        $department = Department::firstOrCreate(['nama_department' => 'Approval Test']);
        return SuratPerintahBayar::create([
            'user_id' => $employee->id, 'department_id' => $department->id,
            'company_id' => \App\Models\Company::firstOrCreate(['kode' => 'APPTEST'], ['nama' => 'Approval Test'])->id,
            'keterangan' => 'Business trip', 'jumlah_total' => 111500, 'terbilang' => 'Four Hundred Fifteen Rupiah and Fifty Sen', 'tanggal_penagihan' => today(), 'tanggal_jatuhtempo' => today()->addDays(10), 'no_invoice' => 'INV-APP', 'customer' => 'Vendor', 'jumlah' => 100000, 'ppn' => 11000, 'pph' => 2000, 'admin' => 2500, 'lampiran' => 'test.pdf', 'informasi_transfer' => 'Bank transfer',
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
        $this->getJson('/api/v1/payment-applications/approvals')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id)
            ->assertJsonPath('data.0.can_approve', true)
            ->assertJsonPath('data.0.created_by', $own->user->name);
        $this->getJson('/api/v1/payment-applications')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_manager_then_finance_approval_records_signatures_and_prevents_replay(): void
    {
        $manager = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Manager']);
        $hr = User::factory()->create(['level' => Role::Admin, 'jabatan' => 'Finance Manager']);
        $record = $this->permit($manager);
        $url = '/api/v1/payment-applications/'.$record->id;
        Sanctum::actingAs($hr);
        $this->postJson($url.'/approve')->assertConflict();
        Sanctum::actingAs($manager);
        $this->postJson($url.'/approve')->assertOk()->assertJsonPath('data.approval_level', 2)
            ->assertJsonPath('data.status', 'Pending Approval')->assertJsonPath('data.can_approve', false);
        $this->postJson($url.'/approve')->assertConflict();
        $this->getJson('/api/v1/payment-applications/approvals')->assertJsonCount(0, 'data');
        $this->assertSame($manager->id, $record->fresh()->approved_by_manager);
        Sanctum::actingAs($hr);
        $this->getJson('/api/v1/payment-applications/approvals')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson($url.'/approve')->assertOk()->assertJsonPath('data.status', 'Approved');
        $this->postJson($url.'/approve')->assertConflict();
        $this->assertSame($hr->id, $record->fresh()->approved_by);
        $this->assertNotNull($record->fresh()->approved_manager_at);
        $this->assertNotNull($record->fresh()->approved_at);
        $this->assertNull($record->fresh()->paid_at);
        $this->assertSame(11000.0, $record->fresh()->ppn);
        $this->assertSame(2000.0, $record->fresh()->pph);
        $this->assertSame(2500.0, $record->fresh()->admin);
    }

    public function test_unauthorized_users_cannot_list_or_decide(): void
    {
        $manager = User::factory()->create(['level' => Role::Superuser]);
        $record = $this->permit($manager);
        $url = '/api/v1/payment-applications/'.$record->id;
        $this->getJson('/api/v1/payment-applications/approvals')->assertUnauthorized();
        $this->postJson($url.'/approve')->assertUnauthorized();
        foreach ([Role::User, Role::Admin, Role::Superadmin] as $role) {
            Sanctum::actingAs(User::withoutEvents(fn () => User::factory()->create(['level' => $role, 'jabatan' => 'Staff'])));
            $this->getJson('/api/v1/payment-applications/approvals')->assertForbidden();
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
        $url = '/api/v1/payment-applications/'.$record->id;
        foreach (['', '   ', str_repeat('a', 5001), ['invalid']] as $note) {
            $this->postJson($url.'/reject', ['rejected_note' => $note])->assertUnprocessable();
        }
        $this->postJson($url.'/reject', ['rejected_note' => 'Insufficient explanation'])->assertOk()
            ->assertJsonPath('data.status', 'Rejected')->assertJsonPath('data.rejected_note', 'Insufficient explanation');
        $this->postJson($url.'/approve')->assertConflict();
        $this->assertSame(1, $record->approvalHistories()->count());
        $cancelled = $this->permit($manager);
        $cancelled->update(['status' => 'Cancelled']);
        $this->postJson('/api/v1/payment-applications/'.$cancelled->id.'/approve')->assertConflict();
        $this->assertSame('Cancelled', $cancelled->fresh()->status);
        $cancelled->update(['status' => 'Paid']);
        $this->postJson('/api/v1/payment-applications/'.$cancelled->id.'/approve')->assertConflict();
        $this->assertSame('Paid', $cancelled->fresh()->status);
    }

    public function test_finance_supervisor_sees_both_stages_but_cannot_approve_own_request(): void
    {
        $finance = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Finance Manager']);
        $direct = $this->permit($finance);
        $stageTwo = $this->permit(User::factory()->create(['level' => Role::Superuser]));
        $stageTwo->update(['approval_level' => 2]);
        $own = $this->permit($finance);
        $own->update(['user_id' => $finance->id, 'approval_level' => 2]);
        Sanctum::actingAs($finance);
        $this->getJson('/api/v1/payment-applications/approvals')->assertOk()->assertJsonCount(2, 'data');
        $this->postJson('/api/v1/payment-applications/'.$own->id.'/approve')->assertForbidden();
        $this->postJson('/api/v1/payment-applications/'.$direct->id.'/approve', ['jumlah_total' => '1.00'])->assertOk()
            ->assertJsonPath('data.status', 'Approved')->assertJsonPath('data.jumlah_total', 111500);
        $this->postJson('/api/v1/payment-applications/'.$stageTwo->id.'/approve')->assertOk()
            ->assertJsonPath('data.status', 'Approved');
    }
}
