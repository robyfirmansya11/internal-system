<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Lembur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_overtime_requires_manager_then_hrd(): void
    {
        $company = $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $staff = $this->user(Role::User, 'Staff', $company, $manager);
        $hrd = $this->user(Role::Admin, Lembur::LEVEL2_JABATAN);
        [$overtime] = $this->overtime($staff, 1);

        $this->assertTrue($overtime->approveByAtasan($manager));
        $this->assertSame(2, $overtime->fresh()->approval_level);
        $this->assertTrue($overtime->fresh()->approveByAdmin($hrd));
        $this->assertSame('Approved', $overtime->fresh()->status);
    }

    public function test_hrd_overtime_is_finally_approved_by_direct_manager(): void
    {
        $company = $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $hrdApplicant = $this->user(Role::Admin, Lembur::LEVEL2_JABATAN, $company, $manager);
        [$overtime] = $this->overtime($hrdApplicant, 1);

        $this->assertTrue($overtime->approveByAtasan($manager));
        $overtime->refresh();
        $this->assertSame('Approved', $overtime->status);
        $this->assertSame(3, $overtime->approval_level);
        $this->assertSame($manager->id, $overtime->approved_by);
    }

    public function test_only_owner_can_cancel_pending_overtime_request(): void
    {
        [$overtime, $staff] = $this->overtime();
        $otherUser = $this->user(Role::User, 'Staff');

        $this->assertFalse($overtime->cancel($otherUser));
        $this->assertTrue($overtime->cancel($staff));
        $this->assertFalse($overtime->fresh()->cancel($staff));
    }

    private function company(): Company
    {
        return Company::create(['nama' => 'Test Company', 'kode' => fake()->unique()->lexify('OVT???')]);
    }

    private function user(Role $role, string $jabatan, ?Company $company = null, ?User $atasan = null): User
    {
        $user = User::factory()->create(['level' => $role, 'jabatan' => $jabatan]);
        if ($company || $atasan) {
            EmployeeProfile::create(['user_id' => $user->id, 'company_id' => $company?->id, 'atasan_id' => $atasan?->id]);
        }
        return $user;
    }

    private function overtime(?User $user = null, int $approvalLevel = 1): array
    {
        $company = $user?->profile?->company ?? $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $staff = $user ?? $this->user(Role::User, 'Staff', $company, $manager);
        $department = Department::firstOrCreate(['nama_department' => 'Test Department']);

        return [Lembur::create([
            'user_id' => $staff->id, 'department_id' => $department->id,
            'bulan_lembur' => now()->format('F Y'), 'tanggal_lembur' => now()->toDateString(),
            'mulai_kerja' => '08:00', 'selesai_kerja' => '17:00',
            'mulai_lembur' => '17:00', 'selesai_lembur' => '20:00',
            'uang_makan' => 0, 'uraian_pekerjaan' => 'Automated test overtime',
            'jumlah_jam_lembur' => 3, 'status' => 'Pending Approval', 'approval_level' => $approvalLevel,
        ]), $staff];
    }
}
