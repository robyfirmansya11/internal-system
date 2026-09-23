<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Keterlambatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LateWorkingPermitWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_late_working_permit_requires_manager_then_hrd(): void
    {
        $company = $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $staff = $this->user(Role::User, 'Staff', $company, $manager);
        $hrd = $this->user(Role::Admin, Keterlambatan::LEVEL2_JABATAN);
        [$permit] = $this->permit($staff, 1);

        $this->assertTrue($permit->approveByAtasan($manager));
        $this->assertSame(2, $permit->fresh()->approval_level);
        $this->assertTrue($permit->fresh()->approveByAdmin($hrd));
        $this->assertSame('Approved', $permit->fresh()->status);
    }

    public function test_hrd_as_direct_manager_completes_late_working_permit_approval(): void
    {
        $company = $this->company();
        $hrdManager = $this->user(Role::Admin, Keterlambatan::LEVEL2_JABATAN);
        $staff = $this->user(Role::User, 'Staff', $company, $hrdManager);
        [$permit] = $this->permit($staff, 1);

        $this->assertTrue($permit->approveByAtasan($hrdManager));
        $permit->refresh();
        $this->assertSame('Approved', $permit->status);
        $this->assertSame($hrdManager->id, $permit->approved_by);
    }

    public function test_only_current_approver_can_reject_and_owner_can_cancel(): void
    {
        [$permit, $staff, $manager] = $this->permit();
        $otherUser = $this->user(Role::User, 'Staff');

        $this->assertFalse($permit->reject($otherUser, 'Unauthorized'));
        $this->assertTrue($permit->reject($manager, 'Reason is incomplete'));
        $permit->refresh();
        $this->assertNotNull($permit->rejected_at);
        $this->assertFalse($permit->cancel($staff));
    }

    private function company(): Company
    {
        return Company::create(['nama' => 'Test Company', 'kode' => fake()->unique()->lexify('LWP???')]);
    }

    private function user(Role $role, string $jabatan, ?Company $company = null, ?User $atasan = null): User
    {
        $user = User::factory()->create(['level' => $role, 'jabatan' => $jabatan]);
        if ($company || $atasan) EmployeeProfile::create(['user_id' => $user->id, 'company_id' => $company?->id, 'atasan_id' => $atasan?->id]);
        return $user;
    }

    private function permit(?User $user = null, int $approvalLevel = 1): array
    {
        $company = $user?->profile?->company ?? $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $staff = $user ?? $this->user(Role::User, 'Staff', $company, $manager);
        $department = Department::firstOrCreate(['nama_department' => 'Test Department']);

        return [Keterlambatan::create([
            'user_id' => $staff->id, 'department_id' => $department->id,
            'tanggal' => now()->toDateString(), 'jam_masuk' => '09:00', 'alasan' => 'Automated test',
            'status' => 'Pending Approval', 'approval_level' => $approvalLevel,
        ]), $staff, $manager];
    }
}
