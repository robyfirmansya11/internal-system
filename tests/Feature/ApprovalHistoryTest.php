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

class ApprovalHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_approval_creates_an_immutable_audit_record(): void
    {
        $company = Company::create(['nama' => 'Alpha', 'kode' => 'ALP']);
        $department = Department::create(['nama_department' => 'Finance']);
        $manager = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Manager']);
        $employee = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        EmployeeProfile::create(['user_id' => $employee->id, 'atasan_id' => $manager->id]);
        $kasbon = Kasbon::create(['company_id' => $company->id, 'department_id' => $department->id, 'user_id' => $employee->id, 'tanggal' => now()->toDateString(), 'keterangan' => 'Advance', 'jumlah_dana' => 100000, 'terbilang' => 'One Hundred Thousand Rupiah', 'informasi_transfer' => 'Test', 'status' => 'Pending Approval', 'approval_level' => 1]);

        $this->assertTrue($kasbon->approveByAtasan($manager));

        $this->assertDatabaseHas('approval_histories', [
            'approvable_type' => Kasbon::class,
            'approvable_id' => $kasbon->id,
            'user_id' => $manager->id,
            'action' => 'Checked',
            'status_before' => 'Pending Approval',
            'status_after' => 'Pending Approval',
            'approval_level' => 2,
        ]);
    }
}
