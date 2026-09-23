<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Department;
use App\Models\FormCuti;
use App\Models\KuotaCuti;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaveQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_annual_leave_approval_deducts_the_employee_quota(): void
    {
        $employee = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $hrd = User::factory()->create(['level' => Role::Admin, 'jabatan' => 'HRD']);
        $quota = KuotaCuti::create([
            'user_id' => $employee->id,
            'tahun' => now()->year,
            'kuota_tahunan' => 12,
            'cuti_terpakai' => 2,
            'sisa_cuti' => 10,
        ]);

        $leave = FormCuti::create([
            'user_id' => $employee->id,
            'department_id' => Department::create(['nama_department' => 'HR'])->id,
            'tahun' => now()->year,
            'tanggal_mulai' => now()->addWeek()->toDateString(),
            'tanggal_selesai' => now()->addDays(8)->toDateString(),
            'jumlah_hari' => 3,
            'jenis_cuti' => 'Cuti Tahunan',
            'alasan' => 'Automated quota test',
            'status' => 'Pending Approval',
            'approval_level' => 3,
        ]);

        $this->assertTrue($leave->approveByAdmin($hrd));
        $this->assertSame('Approved', $leave->fresh()->status);
        $this->assertSame(5, $quota->fresh()->cuti_terpakai);
        $this->assertSame(7, $quota->fresh()->sisa_cuti);
    }

    public function test_leave_cannot_consume_more_than_remaining_quota(): void
    {
        $employee = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        KuotaCuti::create([
            'user_id' => $employee->id,
            'tahun' => now()->year,
            'kuota_tahunan' => 2,
            'cuti_terpakai' => 1,
            'sisa_cuti' => 1,
        ]);

        $leave = FormCuti::create([
            'user_id' => $employee->id,
            'department_id' => Department::create(['nama_department' => 'Finance'])->id,
            'tahun' => now()->year,
            'tanggal_mulai' => now()->addWeek()->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(),
            'jumlah_hari' => 2,
            'jenis_cuti' => 'Cuti Tahunan',
            'alasan' => 'Insufficient quota test',
            'status' => 'Pending Approval',
            'approval_level' => 3,
        ]);

        $this->expectException(ValidationException::class);
        $leave->potongKuota();
    }
}
