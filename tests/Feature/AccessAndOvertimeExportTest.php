<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Department;
use App\Models\Lembur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessAndOvertimeExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_hr_administrators_can_open_user_and_leave_quota_management(): void
    {
        $staff = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $admin = User::factory()->create(['level' => Role::Admin, 'jabatan' => 'HRD']);

        $this->actingAs($staff)->get('/admin/users')->assertForbidden();
        $this->actingAs($staff)->get('/admin/kuota-cutis')->assertForbidden();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/kuota-cutis')->assertOk();
    }

    public function test_overtime_pdf_and_excel_exports_are_available_to_the_authenticated_employee(): void
    {
        $department = Department::create(['nama_department' => 'Operations']);
        $staff = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $this->overtime($staff, $department, 'Approved', '2026-09-15');
        $this->overtime($staff, $department, 'Pending Approval', '2026-09-16');

        $this->actingAs($staff)->get(route('overtime.report.pdf', ['year' => 2026, 'month' => 9]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->actingAs($staff)->get(route('overtime.report.excel', ['year' => 2026, 'month' => 9]))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    private function overtime(User $user, Department $department, string $status, string $date): void
    {
        Lembur::create([
            'user_id' => $user->id, 'department_id' => $department->id,
            'bulan_lembur' => 'September 2026', 'tanggal_lembur' => $date,
            'mulai_kerja' => '08:00', 'selesai_kerja' => '17:00',
            'mulai_lembur' => '17:00', 'selesai_lembur' => '20:00',
            'uang_makan' => 20000, 'uraian_pekerjaan' => 'Export test',
            'jumlah_jam_lembur' => 3, 'status' => $status,
            'approval_level' => $status === 'Approved' ? 3 : 1,
        ]);
    }
}
