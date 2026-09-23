<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Kasbon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanNoteApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_loan_note_requires_manager_then_finance_manager(): void
    {
        $company = $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $staff = $this->user(Role::User, 'Staff', $company, $manager);
        $financeManager = $this->user(Role::Admin, Kasbon::LEVEL2_JABATAN);
        $loan = $this->loan($staff, 1);

        $this->assertTrue($loan->approveByAtasan($manager));
        $loan->refresh();
        $this->assertSame(2, $loan->approval_level);
        $this->assertSame($manager->id, $loan->approved_by_manager);

        $this->assertTrue($loan->approveByAdmin($financeManager));
        $loan->refresh();
        $this->assertSame('Approved', $loan->status);
        $this->assertSame($financeManager->id, $loan->approved_by);
    }

    public function test_manager_loan_note_waits_directly_for_finance_manager(): void
    {
        $company = $this->company();
        $manager = $this->user(Role::Superuser, 'Manager', $company);
        $financeManager = $this->user(Role::Admin, Kasbon::LEVEL2_JABATAN);
        $loan = $this->loan($manager, 2);

        $this->assertFalse($loan->approveByAtasan($manager));
        $this->assertTrue($loan->approveByAdmin($financeManager));
        $this->assertSame('Approved', $loan->fresh()->status);
    }

    public function test_rejected_or_approved_loan_note_cannot_be_cancelled(): void
    {
        $company = $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $staff = $this->user(Role::User, 'Staff', $company, $manager);
        $loan = $this->loan($staff, 1);

        $this->assertTrue($loan->reject($manager, 'Insufficient supporting documents'));
        $this->assertFalse($loan->cancel($staff));

        $cancellableLoan = $this->loan($staff, 1);
        $this->assertTrue($cancellableLoan->cancel($staff));
        $this->assertSame('Cancelled', $cancellableLoan->fresh()->status);
    }

    private function company(): Company
    {
        return Company::create(['nama' => 'Test Company', 'kode' => fake()->unique()->lexify('LOA???')]);
    }

    private function user(Role $role, string $jabatan, ?Company $company = null, ?User $atasan = null): User
    {
        $user = User::factory()->create(['level' => $role, 'jabatan' => $jabatan]);

        if ($company || $atasan) {
            EmployeeProfile::create(['user_id' => $user->id, 'company_id' => $company?->id, 'atasan_id' => $atasan?->id]);
        }

        return $user;
    }

    private function loan(User $user, int $approvalLevel): Kasbon
    {
        $department = Department::firstOrCreate(['nama_department' => 'Test Department']);
        $company = $user->profile?->company ?? $this->company();

        return Kasbon::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'tanggal' => now()->toDateString(),
            'keterangan' => 'Automated test loan note',
            'jumlah_dana' => 100000,
            'terbilang' => 'One Hundred Thousand Rupiah',
            'status' => 'Pending Approval',
            'approval_level' => $approvalLevel,
        ]);
    }
}
