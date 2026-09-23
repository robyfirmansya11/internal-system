<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\PermohonanStempel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampApplicationApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_stamp_application_is_finally_approved_by_direct_manager(): void
    {
        [$request, $manager] = $this->request();

        $this->assertTrue($request->approveByAtasan($manager));
        $request->refresh();
        $this->assertSame('Approved', $request->status);
        $this->assertSame(2, $request->approval_level);
        $this->assertSame($manager->id, $request->approved_by);
    }

    public function test_manager_with_a_direct_supervisor_cannot_skip_that_supervisor(): void
    {
        $company = Company::create(['nama' => 'Test Company', 'kode' => 'STM']);
        $supervisor = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Manager']);
        $managerApplicant = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Manager']);
        EmployeeProfile::create(['user_id' => $managerApplicant->id, 'company_id' => $company->id, 'atasan_id' => $supervisor->id]);
        $request = $this->makeRequest($managerApplicant, $company);

        $this->assertFalse($request->approveByAtasan($managerApplicant));
        $this->assertTrue($request->approveByAtasan($supervisor));
        $this->assertSame('Approved', $request->fresh()->status);
    }

    public function test_rejected_stamp_application_cannot_be_cancelled(): void
    {
        [$request, $manager, $staff] = $this->request();
        $this->assertTrue($request->reject($manager, 'Supporting letter is missing'));
        $this->assertFalse($request->cancel($staff));
    }

    private function request(): array
    {
        $company = Company::create(['nama' => 'Test Company', 'kode' => fake()->unique()->lexify('STP???')]);
        $manager = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Manager']);
        $staff = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        EmployeeProfile::create(['user_id' => $staff->id, 'company_id' => $company->id, 'atasan_id' => $manager->id]);
        return [$this->makeRequest($staff, $company), $manager, $staff];
    }

    private function makeRequest(User $user, Company $company): PermohonanStempel
    {
        $department = Department::firstOrCreate(['nama_department' => 'Test Department']);
        return PermohonanStempel::create([
            'company_id' => $company->id, 'user_id' => $user->id, 'department_id' => $department->id,
            'tanggal' => now()->toDateString(), 'tujuan' => 'Automated test', 'status' => 'Pending Approval', 'approval_level' => 1,
        ]);
    }
}
