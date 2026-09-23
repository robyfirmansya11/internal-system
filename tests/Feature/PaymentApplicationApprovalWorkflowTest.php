<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\SuratPerintahBayar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentApplicationApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_payment_application_requires_manager_then_finance_manager(): void
    {
        $company = $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $staff = $this->user(Role::User, 'Staff', $company, $manager);
        $financeManager = $this->user(Role::Admin, SuratPerintahBayar::LEVEL2_JABATAN);
        $request = $this->paymentApplication($staff, 1);

        $this->assertTrue($request->approveByAtasan($manager));
        $request->refresh();
        $this->assertSame('Pending Approval', $request->status);
        $this->assertSame(2, $request->approval_level);
        $this->assertSame($manager->id, $request->approved_by_manager);

        $this->assertTrue($request->approveByAdmin($financeManager));
        $request->refresh();
        $this->assertSame('Approved', $request->status);
        $this->assertSame(3, $request->approval_level);
        $this->assertSame($financeManager->id, $request->approved_by);
    }

    public function test_finance_manager_as_direct_manager_completes_both_approval_steps(): void
    {
        $company = $this->company();
        $financeManager = $this->user(Role::Admin, SuratPerintahBayar::LEVEL2_JABATAN);
        $staff = $this->user(Role::User, 'Staff', $company, $financeManager);
        $request = $this->paymentApplication($staff, 1);

        $this->assertTrue($request->approveByAtasan($financeManager));
        $request->refresh();
        $this->assertSame('Approved', $request->status);
        $this->assertSame(3, $request->approval_level);
        $this->assertSame($financeManager->id, $request->approved_by_manager);
        $this->assertSame($financeManager->id, $request->approved_by);
    }

    public function test_only_current_approver_can_reject_and_owner_can_cancel_before_decision(): void
    {
        $company = $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $staff = $this->user(Role::User, 'Staff', $company, $manager);
        $otherUser = $this->user(Role::User, 'Staff');
        $request = $this->paymentApplication($staff, 1);

        $this->assertFalse($request->reject($otherUser, 'Unauthorized'));
        $this->assertTrue($request->reject($manager, 'Invoice is incomplete'));
        $request->refresh();
        $this->assertSame('Rejected', $request->status);
        $this->assertFalse($request->cancel($staff));

        $cancellableRequest = $this->paymentApplication($staff, 1);
        $this->assertFalse($cancellableRequest->cancel($otherUser));
        $this->assertTrue($cancellableRequest->cancel($staff));
        $this->assertSame('Cancelled', $cancellableRequest->fresh()->status);
    }

    private function company(): Company
    {
        return Company::create(['nama' => 'Test Company', 'kode' => fake()->unique()->lexify('PAY???')]);
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

    private function paymentApplication(User $user, int $approvalLevel): SuratPerintahBayar
    {
        $department = Department::firstOrCreate(['nama_department' => 'Test Department']);
        $company = $user->profile?->company ?? $this->company();

        return SuratPerintahBayar::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'tanggal_penagihan' => now()->toDateString(),
            'tanggal_jatuhtempo' => now()->addWeek()->toDateString(),
            'no_invoice' => fake()->unique()->bothify('INV-####'),
            'customer' => 'Test Vendor',
            'jumlah' => 100000,
            'jumlah_total' => 100000,
            'pembayaran_tahap' => '1',
            'jumlah_lampiran' => '1',
            'status' => 'Pending Approval',
            'approval_level' => $approvalLevel,
        ]);
    }
}
