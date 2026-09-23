<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Lembur;
use App\Models\User;
use App\Services\OvertimeReportQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeReportQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_only_sees_their_approved_overtime_in_the_report(): void
    {
        $department = Department::create(['nama_department' => 'Operations']);
        $staff = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $otherStaff = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);

        $visible = $this->overtime($staff, $department, 'Approved', now()->setDate(2026, 9, 10));
        $this->overtime($staff, $department, 'Pending Approval', now()->setDate(2026, 9, 11));
        $this->overtime($otherStaff, $department, 'Approved', now()->setDate(2026, 9, 10));

        $records = app(OvertimeReportQueryService::class)->build($staff)->get();

        $this->assertCount(1, $records);
        $this->assertTrue($records->first()->is($visible));
    }

    public function test_manager_report_scope_includes_direct_reports_across_departments_and_filters_by_month(): void
    {
        $manager = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Manager']);
        $firstDepartment = Department::create(['nama_department' => 'Finance']);
        $secondDepartment = Department::create(['nama_department' => 'Operations']);
        $directReport = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $otherEmployee = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        EmployeeProfile::create(['user_id' => $directReport->id, 'atasan_id' => $manager->id]);

        $first = $this->overtime($directReport, $firstDepartment, 'Approved', now()->setDate(2026, 9, 10));
        $second = $this->overtime($directReport, $secondDepartment, 'Approved', now()->setDate(2026, 9, 11));
        $this->overtime($directReport, $firstDepartment, 'Approved', now()->setDate(2026, 8, 10));
        $this->overtime($otherEmployee, $firstDepartment, 'Approved', now()->setDate(2026, 9, 10));

        $records = app(OvertimeReportQueryService::class)->build($manager, [
            'year' => 2026,
            'month' => 9,
        ])->get();

        $this->assertCount(2, $records);
        $this->assertTrue($records->contains($first));
        $this->assertTrue($records->contains($second));
        $this->assertSame([$directReport->id], app(OvertimeReportQueryService::class)
            ->accessibleEmployees($manager)
            ->pluck('id')
            ->all());
    }

    private function overtime(User $user, Department $department, string $status, \Carbon\Carbon $date): Lembur
    {
        return Lembur::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'bulan_lembur' => $date->format('F Y'),
            'tanggal_lembur' => $date->toDateString(),
            'mulai_kerja' => '08:00',
            'selesai_kerja' => '17:00',
            'mulai_lembur' => '17:00',
            'selesai_lembur' => '20:00',
            'uang_makan' => 20000,
            'uraian_pekerjaan' => 'Automated report test',
            'jumlah_jam_lembur' => 3,
            'status' => $status,
            'approval_level' => $status === 'Approved' ? 3 : 1,
        ]);
    }
}
