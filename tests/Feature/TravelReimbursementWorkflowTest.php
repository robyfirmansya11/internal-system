<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\PerjalananDinas;
use App\Models\PerjalananDinasDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelReimbursementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_changes_automatically_recalculate_travel_total(): void
    {
        [$request] = $this->request();

        PerjalananDinasDetail::create($this->detail($request, 1000, 2000, 2, 3000, 1, 500, 250));
        PerjalananDinasDetail::create($this->detail($request, 500, 1000, 1, 0, 0, 0, 0));

        $request->refresh();
        $this->assertSame('10250.00', $request->total);
        $this->assertSame('Ten Thousand Two Hundred Fifty Rupiah', $request->terbilang);
    }

    public function test_staff_travel_reimbursement_requires_manager_then_finance_manager(): void
    {
        $company = $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $staff = $this->user(Role::User, 'Staff', $company, $manager);
        $financeManager = $this->user(Role::Admin, PerjalananDinas::LEVEL2_JABATAN);
        [$request] = $this->request($staff, 1);

        $this->assertTrue($request->approveByAtasan($manager));
        $this->assertSame(2, $request->fresh()->approval_level);
        $this->assertTrue($request->fresh()->approveByAdmin($financeManager));
        $this->assertSame('Approved', $request->fresh()->status);
    }

    public function test_only_owner_can_cancel_before_travel_request_is_decided(): void
    {
        [$request, $staff, $manager] = $this->request();
        $otherUser = $this->user(Role::User, 'Staff');

        $this->assertFalse($request->cancel($otherUser));
        $this->assertTrue($request->cancel($staff));
        $this->assertFalse($request->fresh()->cancel($staff));

        [$rejectedRequest] = $this->request($staff, 1);
        $this->assertTrue($rejectedRequest->reject($manager, 'Missing travel receipt'));
        $this->assertFalse($rejectedRequest->cancel($staff));
    }

    private function company(): Company
    {
        return Company::create(['nama' => 'Test Company', 'kode' => fake()->unique()->lexify('TRV???')]);
    }

    private function user(Role $role, string $jabatan, ?Company $company = null, ?User $atasan = null): User
    {
        $user = User::factory()->create(['level' => $role, 'jabatan' => $jabatan]);
        if ($company || $atasan) {
            EmployeeProfile::create(['user_id' => $user->id, 'company_id' => $company?->id, 'atasan_id' => $atasan?->id]);
        }
        return $user;
    }

    private function request(?User $user = null, int $approvalLevel = 1): array
    {
        $company = $user?->profile?->company ?? $this->company();
        $manager = $this->user(Role::Superuser, 'Manager');
        $staff = $user ?? $this->user(Role::User, 'Staff', $company, $manager);
        $department = Department::firstOrCreate(['nama_department' => 'Test Department']);

        return [PerjalananDinas::create([
            'company_id' => $company->id, 'user_id' => $staff->id, 'department_id' => $department->id,
            'keterangan' => 'Automated test travel reimbursement', 'jumlah_lampiran' => 0,
            'total' => 0, 'status' => 'Pending Approval', 'approval_level' => $approvalLevel,
        ]), $staff, $manager];
    }

    private function detail(PerjalananDinas $request, float $transport, float $allowance, int $days, float $hotel, int $nights, float $misc, float $other): array
    {
        return [
            'perjalanan_dinas_id' => $request->id, 'tanggal_berangkat' => now()->toDateString(),
            'tempat_berangkat' => 'A', 'tanggal_tujuan' => now()->addDay()->toDateString(), 'tempat_tujuan' => 'B',
            'jumlah_hari' => $days, 'amount_transportasi' => $transport, 'amount_tunjangan' => $allowance,
            'lama_hotel' => $nights, 'amount_hotel' => $hotel, 'misc' => $misc, 'amount_other' => $other,
        ];
    }
}
