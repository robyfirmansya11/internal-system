<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Kasbon;
use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActivityLogAndWorkflowNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_update_records_actor_and_changed_values(): void
    {
        $admin = User::factory()->create(['level' => Role::Admin, 'jabatan' => 'HRD']);
        $employee = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $this->actingAs($admin);

        $employee->update(['jabatan' => 'Manager']);

        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'subject_type' => User::class, 'subject_id' => $employee->id, 'event' => 'updated']);
    }

    public function test_workflow_action_notifies_the_request_owner(): void
    {
        Notification::fake();
        $company = Company::create(['nama' => 'Alpha', 'kode' => 'ALP']);
        $department = Department::create(['nama_department' => 'Finance']);
        $manager = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Manager']);
        $owner = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        EmployeeProfile::create(['user_id' => $owner->id, 'atasan_id' => $manager->id]);
        $document = Kasbon::create(['company_id' => $company->id, 'department_id' => $department->id, 'user_id' => $owner->id, 'tanggal' => now(), 'keterangan' => 'Test', 'jumlah_dana' => 1000, 'terbilang' => 'One Thousand Rupiah', 'informasi_transfer' => 'Test', 'status' => 'Pending Approval', 'approval_level' => 1]);

        $document->approveByAtasan($manager);

        Notification::assertSentTo($owner, WorkflowStatusNotification::class);
    }
}
