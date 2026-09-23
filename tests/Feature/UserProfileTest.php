<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\User;
use App\Filament\Pages\MyProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_uses_primary_department_and_direct_supervisor_from_profile(): void
    {
        $supervisor = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Manager']);
        $employee = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $secondary = Department::create(['nama_department' => 'Operations']);
        $primary = Department::create(['nama_department' => 'Finance']);

        $employee->departments()->attach($secondary->id, ['is_primary' => false]);
        $employee->departments()->attach($primary->id, ['is_primary' => true]);
        EmployeeProfile::create([
            'user_id' => $employee->id,
            'atasan_id' => $supervisor->id,
            'no_hp' => '08123456789',
        ]);

        $employee->refresh()->load(['departments', 'profile.atasan']);

        $this->assertSame('Finance', $employee->department);
        $this->assertTrue($employee->primaryDepartment->is($primary));
        $this->assertTrue($supervisor->isAtasanOf($employee));
        $this->assertTrue($employee->atasan->is($supervisor));
        $this->assertSame('08123456789', $employee->profile->no_hp);
    }

    public function test_user_role_helpers_distinguish_hrd_and_finance_manager(): void
    {
        $hrd = User::factory()->create(['level' => Role::Admin, 'jabatan' => 'HRD']);
        $financeManager = User::factory()->create(['level' => Role::Admin, 'jabatan' => 'Finance Manager']);

        $this->assertTrue($hrd->isHRD());
        $this->assertFalse($hrd->isFinanceManager());
        $this->assertTrue($financeManager->isFinanceManager());
        $this->assertTrue($financeManager->canApprove());
    }

    public function test_my_profile_saves_personal_data_without_allowing_staff_to_change_hr_fields(): void
    {
        $user = User::factory()->create([
            'level' => Role::User,
            'jabatan' => 'Staff',
            'email' => 'profile@example.test',
        ]);

        $this->actingAs($user);

        Livewire::test(MyProfile::class)
            ->set('data.name', 'Updated Profile')
            ->set('data.email', 'updated-profile@example.test')
            ->set('data.no_hp', '08123456789')
            ->set('data.alamat', 'Jakarta')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Profile',
            'email' => 'updated-profile@example.test',
            'jabatan' => 'Staff',
        ]);
        $this->assertDatabaseHas('employee_profiles', [
            'user_id' => $user->id,
            'no_hp' => '08123456789',
            'alamat' => 'Jakarta',
        ]);
    }
}
