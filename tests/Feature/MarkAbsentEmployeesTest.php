<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\FormCuti;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkAbsentEmployeesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }

    public function test_command_skips_approved_leave_and_resigned_employees(): void
    {
        Carbon::setTestNow('2026-09-18 15:30:00');
        $active = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $onLeave = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $resigned = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        EmployeeProfile::create(['user_id' => $resigned->id, 'tanggal_keluar' => '2026-09-17']);
        FormCuti::create(['user_id' => $onLeave->id, 'department_id' => Department::create(['nama_department' => 'HR'])->id, 'tahun' => 2026, 'tanggal_mulai' => '2026-09-18', 'tanggal_selesai' => '2026-09-18', 'jumlah_hari' => 1, 'jenis_cuti' => 'Cuti Khusus', 'alasan' => 'Test', 'status' => 'Approved', 'approval_level' => 4]);

        $this->artisan('attendance:mark-absent')->assertSuccessful();

        $this->assertDatabaseHas('attendances', ['user_id' => $active->id, 'status' => 'absent']);
        $this->assertDatabaseMissing('attendances', ['user_id' => $onLeave->id]);
        $this->assertDatabaseMissing('attendances', ['user_id' => $resigned->id]);
    }

    public function test_command_skips_a_configured_holiday(): void
    {
        Carbon::setTestNow('2026-09-18 15:30:00');
        $user = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        Holiday::create(['date' => '2026-09-18', 'name' => 'Test Holiday']);

        $this->artisan('attendance:mark-absent')->assertSuccessful();

        $this->assertDatabaseMissing('attendances', ['user_id' => $user->id]);
    }
}
