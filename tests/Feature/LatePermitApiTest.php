<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Keterlambatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LatePermitApiTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.connections.'.config('database.default').'.database') !== 'insys_db_testing') {
            throw new \RuntimeException('Tests require isolated insys_db_testing database.');
        }
    }

    private function applicant(string $jabatan = 'Staff'): User
    {
        $user = User::factory()->create(['level' => Role::User, 'jabatan' => $jabatan]);
        $user->departments()->attach(Department::firstOrCreate(['nama_department' => 'API Test']));
        return $user;
    }

    private function payload(): array
    {
        return ['tanggal' => now()->toDateString(), 'jam_masuk' => '09:15', 'alasan' => 'Kendaraan bermasalah'];
    }

    public function test_authentication_is_required(): void
    {
        $this->getJson('/api/v1/late-working-permits')->assertUnauthorized();
        $this->postJson('/api/v1/late-working-permits', $this->payload())->assertUnauthorized();
    }

    public function test_owner_and_workflow_cannot_be_forged(): void
    {
        $user = $this->applicant();
        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/late-working-permits', array_merge($this->payload(), [
            'user_id' => 999, 'department_id' => 999, 'status' => 'Approved', 'approval_level' => 3,
        ]))->assertCreated()->assertJsonPath('data.status', 'Pending Approval')
            ->assertJsonPath('data.approval_level', 2)->assertJsonPath('data.jam_masuk', '09:15');
        $this->assertSame($user->id, Keterlambatan::findOrFail($response->json('data.id'))->user_id);
    }

    public function test_manager_and_hrd_initial_workflows_match_filament(): void
    {
        $manager = User::factory()->create(['level' => Role::Superuser]);
        $staff = $this->applicant();
        EmployeeProfile::create(['user_id' => $staff->id, 'atasan_id' => $manager->id]);
        Sanctum::actingAs($staff);
        $this->postJson('/api/v1/late-working-permits', $this->payload())->assertCreated()
            ->assertJsonPath('data.approval_level', 1);
        $hrd = $this->applicant('HRD');
        Sanctum::actingAs($hrd);
        $this->postJson('/api/v1/late-working-permits', $this->payload())->assertCreated()
            ->assertJsonPath('data.status', 'Approved')->assertJsonPath('data.can_cancel', false);
        EmployeeProfile::create(['user_id' => $hrd->id, 'atasan_id' => $manager->id]);
        $hrd->unsetRelation('profile');
        $this->postJson('/api/v1/late-working-permits', $this->payload())->assertCreated()
            ->assertJsonPath('data.status', 'Pending Approval')->assertJsonPath('data.approval_level', 1);
    }

    public function test_invalid_fields_and_future_dates_are_rejected(): void
    {
        Sanctum::actingAs($this->applicant());
        $this->postJson('/api/v1/late-working-permits', [
            'tanggal' => now()->addDay()->toDateString(), 'jam_masuk' => '25:00', 'alasan' => ' ',
        ])->assertUnprocessable()->assertJsonValidationErrors(['tanggal', 'jam_masuk', 'alasan']);
        $this->assertDatabaseCount('form_keterlambatan', 0);
    }

    public function test_superuser_and_missing_department_cannot_submit(): void
    {
        Sanctum::actingAs(User::factory()->create(['level' => Role::Superuser]));
        $this->postJson('/api/v1/late-working-permits', $this->payload())->assertForbidden();
        Sanctum::actingAs(User::factory()->create(['level' => Role::User]));
        $this->postJson('/api/v1/late-working-permits', $this->payload())->assertUnprocessable();
    }

    public function test_ownership_and_cancellation_rules(): void
    {
        $owner = $this->applicant();
        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/v1/late-working-permits', $this->payload())->assertCreated()->json('data.id');
        Sanctum::actingAs($this->applicant());
        $this->getJson('/api/v1/late-working-permits')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/late-working-permits/'.$id)->assertNotFound();
        $this->postJson('/api/v1/late-working-permits/'.$id.'/cancel')->assertNotFound();
        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/late-working-permits/'.$id)->assertOk();
        $this->postJson('/api/v1/late-working-permits/'.$id.'/cancel')->assertOk()
            ->assertJsonPath('data.status', 'Cancelled')->assertJsonPath('data.can_cancel', false);
        $this->postJson('/api/v1/late-working-permits/'.$id.'/cancel')->assertUnprocessable();
        foreach (['Approved', 'Rejected'] as $status) {
            Keterlambatan::findOrFail($id)->update(['status' => $status]);
            $this->postJson('/api/v1/late-working-permits/'.$id.'/cancel')->assertUnprocessable();
        }
    }
}
