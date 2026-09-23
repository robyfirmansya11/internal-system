<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Department;
use App\Models\FormCuti;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unrelated_employee_cannot_print_another_employees_leave_request(): void
    {
        $owner = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $otherEmployee = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $leave = FormCuti::create([
            'user_id' => $owner->id,
            'department_id' => Department::create(['nama_department' => 'Operations'])->id,
            'tahun' => now()->year,
            'tanggal_mulai' => now()->addWeek()->toDateString(),
            'tanggal_selesai' => now()->addDays(8)->toDateString(),
            'jumlah_hari' => 2,
            'jenis_cuti' => 'Cuti Khusus',
            'alasan' => 'Private leave request',
            'status' => 'Pending Approval',
            'approval_level' => 1,
        ]);

        $this->actingAs($otherEmployee)
            ->get(route('cuti.print', $leave->id))
            ->assertForbidden();
    }

}
