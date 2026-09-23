<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\NotaPenggantianBiaya;
use App\Models\NotaPenggantianBiayaDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotaPenggantianBiayaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_and_in_words_are_recalculated_from_expense_details(): void
    {
        [$request, $staff] = $this->makeRequest();

        NotaPenggantianBiayaDetail::create([
            'nota_penggantian_biaya_id' => $request->id,
            'keterangan' => 'Office supplies',
            'jumlah' => 5000,
        ]);
        NotaPenggantianBiayaDetail::create([
            'nota_penggantian_biaya_id' => $request->id,
            'keterangan' => 'Courier fee',
            'jumlah' => 3000,
        ]);

        $request->refresh();

        $this->assertSame('8000.00', $request->jumlah_total);
        $this->assertSame('8000.00', $request->jumlah);
        $this->assertSame('Eight Thousand Rupiah', $request->terbilang);
        $this->assertSame($staff->id, $request->user_id);
    }

    public function test_manager_then_finance_manager_can_approve_request(): void
    {
        [$request, $staff, $manager] = $this->makeRequest();
        $financeManager = User::factory()->create([
            'level' => Role::Admin,
            'jabatan' => NotaPenggantianBiaya::LEVEL2_JABATAN,
        ]);

        $request->update(['status' => 'Pending Approval', 'approval_level' => 1]);

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
        $this->assertSame($staff->id, $request->user_id);
    }

    public function test_only_the_current_approver_can_reject_and_only_owner_can_cancel(): void
    {
        [$request, $staff, $manager] = $this->makeRequest();
        $otherUser = User::factory()->create(['level' => Role::User]);
        $request->update(['status' => 'Pending Approval', 'approval_level' => 1]);

        $this->assertFalse($request->reject($otherUser, 'Not authorized'));
        $this->assertTrue($request->reject($manager, 'Missing receipt'));
        $request->refresh();
        $this->assertSame('Rejected', $request->status);
        $this->assertSame($manager->id, $request->rejected_by);
        $this->assertSame('Missing receipt', $request->rejected_note);

        [$newRequest, $newRequestOwner] = $this->makeRequest();
        $this->assertFalse($newRequest->cancel($otherUser));
        $this->assertTrue($newRequest->cancel($newRequestOwner));
        $this->assertSame('Cancelled', $newRequest->fresh()->status);
    }

    /** @return array{NotaPenggantianBiaya, User, User} */
    private function makeRequest(): array
    {
        $company = Company::create(['nama' => 'Test Company', 'kode' => 'TST']);
        $department = Department::create(['nama_department' => 'Test Department']);
        $manager = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Manager']);
        $staff = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        EmployeeProfile::create(['user_id' => $staff->id, 'company_id' => $company->id, 'atasan_id' => $manager->id]);

        $request = NotaPenggantianBiaya::create([
            'company_id' => $company->id,
            'user_id' => $staff->id,
            'department_id' => $department->id,
            'tanggal' => now(),
            'keterangan' => '',
            'jumlah' => 0,
            'jumlah_total' => 0,
            'jumlah_lampiran' => 0,
            'status' => 'Submitted',
            'approval_level' => 0,
        ]);

        return [$request, $staff, $manager];
    }
}
