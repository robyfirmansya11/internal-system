<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Lembur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OvertimeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.connections.'.config('database.default').'.database') !== 'insys_db_testing') {
            throw new \RuntimeException('Overtime API tests require the isolated insys_db_testing database.');
        }
    }

    private function applicant(string $jabatan = 'Staff'): User
    {
        $user = User::factory()->create(['level' => Role::User, 'jabatan' => $jabatan]);
        $department = Department::firstOrCreate(['nama_department' => 'API Test']);
        $user->departments()->attach($department);
        return $user;
    }

    private function payload(): array
    {
        return [
            'bulan_lembur' => '2026-09', 'tanggal_lembur' => '2026-09-21',
            'mulai_kerja' => '08:00', 'selesai_kerja' => '17:00',
            'mulai_lembur' => '17:30', 'selesai_lembur' => '19:00',
            'uang_makan' => 25000, 'uraian_pekerjaan' => 'Test pengajuan mobile',
        ];
    }

    public function test_authentication_is_required(): void
    {
        $this->getJson('/api/v1/overtime')->assertUnauthorized();
        $this->postJson('/api/v1/overtime', $this->payload())->assertUnauthorized();
    }

    public function test_creation_uses_authenticated_owner_and_calculates_hours(): void
    {
        $user = $this->applicant();
        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/overtime', array_merge($this->payload(), [
            'user_id' => 999, 'department_id' => 999, 'status' => 'Approved',
            'approval_level' => 3, 'jumlah_jam_lembur' => 999,
        ]))->assertCreated()->assertJsonPath('data.jumlah_jam_lembur', 1.5)
            ->assertJsonPath('data.status', 'Pending Approval')->assertJsonPath('data.approval_level', 2);
        $this->assertSame($user->id, Lembur::findOrFail($response->json('data.id'))->user_id);
    }

    public function test_applicant_with_manager_starts_at_level_one(): void
    {
        $user = $this->applicant();
        $manager = User::factory()->create(['level' => Role::Superuser]);
        EmployeeProfile::create(['user_id' => $user->id, 'atasan_id' => $manager->id]);
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/overtime', $this->payload())->assertCreated()
            ->assertJsonPath('data.approval_level', 1);
    }

    public function test_hrd_without_manager_and_superuser_cannot_submit(): void
    {
        $hrd = $this->applicant('HRD');
        Sanctum::actingAs($hrd);
        $this->postJson('/api/v1/overtime', $this->payload())->assertUnprocessable();
        Sanctum::actingAs(User::factory()->create(['level' => Role::Superuser]));
        $this->postJson('/api/v1/overtime', $this->payload())->assertForbidden();
    }

    public function test_invalid_times_and_amount_are_rejected(): void
    {
        Sanctum::actingAs($this->applicant());
        $this->postJson('/api/v1/overtime', array_merge($this->payload(), [
            'selesai_lembur' => '16:00', 'uang_makan' => -1,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['selesai_lembur', 'uang_makan']);
        $this->assertDatabaseCount('lemburs', 0);
    }

    public function test_list_detail_and_cancel_are_scoped_to_owner(): void
    {
        $owner = $this->applicant();
        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/v1/overtime', $this->payload())->assertCreated()->json('data.id');
        Sanctum::actingAs($this->applicant());
        $this->getJson('/api/v1/overtime')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/overtime/'.$id)->assertNotFound();
        $this->postJson('/api/v1/overtime/'.$id.'/cancel')->assertNotFound();
        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/overtime')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/v1/overtime/'.$id.'/cancel')->assertOk()
            ->assertJsonPath('data.status', 'Cancelled')->assertJsonPath('data.can_cancel', false);
        $this->postJson('/api/v1/overtime/'.$id.'/cancel')->assertUnprocessable();
    }

    public function test_approved_request_cannot_be_cancelled(): void
    {
        Sanctum::actingAs($this->applicant());
        $id = $this->postJson('/api/v1/overtime', $this->payload())->assertCreated()->json('data.id');
        Lembur::findOrFail($id)->update(['status' => 'Approved']);
        $this->postJson('/api/v1/overtime/'.$id.'/cancel')->assertUnprocessable();
    }
}
