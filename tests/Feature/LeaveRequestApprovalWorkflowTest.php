<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\FormCuti;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_leave_requires_manager_then_hrd_approval(): void
    {
        $company = $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $hrd = $this->user(Role::Admin, 'HRD');
        $staff = $this->user(Role::User, 'Staff', $company, $manager);
        $request = $this->leaveRequest($staff, 1);

        $this->assertTrue($request->approveByAtasan($manager));
        $request->refresh();
        $this->assertSame(3, $request->approval_level);
        $this->assertSame($manager->id, $request->approved_by_manager);

        $this->assertTrue($request->approveByAdmin($hrd));
        $request->refresh();
        $this->assertSame('Approved', $request->status);
        $this->assertSame(4, $request->approval_level);
        $this->assertSame($hrd->id, $request->approved_by_hrd);
    }

    public function test_manager_leave_requires_finance_manager_then_hrd_approval(): void
    {
        $company = $this->company();
        $manager = $this->user(Role::Superuser, 'Manager', $company);
        $financeManager = $this->user(Role::Admin, 'Finance Manager');
        $hrd = $this->user(Role::Admin, 'HRD');
        $request = $this->leaveRequest($manager, FormCuti::initialApprovalLevel($manager));

        $this->assertSame(2, $request->approval_level);
        $this->assertTrue($request->approveByFinanceManager($financeManager));
        $request->refresh();
        $this->assertSame(3, $request->approval_level);
        $this->assertSame($financeManager->id, $request->approved_by_finance_manager);

        $this->assertTrue($request->approveByAdmin($hrd));
        $this->assertSame('Approved', $request->fresh()->status);
    }

    public function test_finance_manager_leave_goes_directly_to_hrd(): void
    {
        $financeManager = $this->user(Role::Admin, 'Finance Manager');
        $hrd = $this->user(Role::Admin, 'HRD');
        $request = $this->leaveRequest($financeManager, FormCuti::initialApprovalLevel($financeManager));

        $this->assertSame(3, $request->approval_level);
        $this->assertTrue($request->approveByAdmin($hrd));
        $request->refresh();
        $this->assertSame('Approved', $request->status);
        $this->assertSame($hrd->id, $request->approved_by_hrd);
        $this->assertNull($request->approved_by_finance_manager);
    }

    public function test_hrd_leave_is_finally_approved_by_direct_manager(): void
    {
        $company = $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $hrd = $this->user(Role::Admin, 'HRD', $company, $manager);
        $request = $this->leaveRequest($hrd, FormCuti::initialApprovalLevel($hrd));

        $this->assertSame(1, $request->approval_level);
        $this->assertTrue($request->approveByAtasan($manager));
        $request->refresh();
        $this->assertSame('Approved', $request->status);
        $this->assertSame(4, $request->approval_level);
        $this->assertSame($manager->id, $request->approved_by);
    }

    private function company(): Company
    {
        return Company::create(['nama' => 'Test Company', 'kode' => fake()->unique()->lexify('TST???')]);
    }

    private function user(Role $role, string $jabatan, ?Company $company = null, ?User $atasan = null): User
    {
        $user = User::factory()->create(['level' => $role, 'jabatan' => $jabatan]);

        if ($company || $atasan) {
            EmployeeProfile::create([
                'user_id' => $user->id,
                'company_id' => $company?->id,
                'atasan_id' => $atasan?->id,
            ]);
        }

        return $user;
    }

    private function leaveRequest(User $user, int $approvalLevel): FormCuti
    {
        $department = Department::firstOrCreate(['nama_department' => 'Test Department']);

        return FormCuti::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'tahun' => now()->year,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'jumlah_hari' => 2,
            'jenis_cuti' => 'Cuti Khusus',
            'alasan' => 'Automated test leave request',
            'status' => 'Pending Approval',
            'approval_level' => $approvalLevel,
        ]);
    }
}
