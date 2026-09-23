<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiAuthenticationAndAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_api_login_returns_a_sanctum_token_for_valid_credentials(): void
    {
        $user = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff', 'email' => 'employee@example.test', 'password' => 'password']);

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['token']);
    }

    public function test_api_login_is_rate_limited_after_five_failed_attempts(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
                ->postJson('/api/v1/login', ['email' => 'limit@example.test', 'password' => 'incorrect'])
                ->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->postJson('/api/v1/login', ['email' => 'limit@example.test', 'password' => 'incorrect'])
            ->assertTooManyRequests();
    }

    public function test_clock_in_requires_a_reason_when_outside_office_radius(): void
    {
        Storage::fake('private');
        Carbon::setTestNow('2026-09-18 09:00:00');
        $user = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        OfficeLocation::create(['name' => 'Head Office', 'latitude' => -6.20000000, 'longitude' => 106.81666600, 'radius' => 100, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.30000000, 'longitude' => 106.91666600,
            'photo' => UploadedFile::fake()->image('selfie.jpg'), 'reason' => 'Traffic', 'gps_accuracy' => 10, 'device_id' => 'device-radius',
        ])->assertUnprocessable()->assertJsonPath('is_outside_radius', true);
    }

    public function test_clock_in_rejects_mock_location_and_records_gps_audit_data(): void
    {
        Storage::fake('private');
        Carbon::setTestNow('2026-09-18 09:00:00');
        $user = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.20010000, 'longitude' => 106.81670000,
            'photo' => UploadedFile::fake()->image('mock.jpg'), 'reason' => 'Traffic', 'gps_accuracy' => 10, 'device_id' => 'device-mock',
            'is_mock_location' => true,
        ])->assertUnprocessable();

        $this->actingAs($user, 'sanctum')->post('/api/v1/attendance/clock-in', [
            'latitude' => -6.20010000, 'longitude' => 106.81670000,
            'photo' => UploadedFile::fake()->image('verified.jpg'), 'reason' => 'Traffic',
            'gps_accuracy' => 12.5, 'device_id' => 'device-test-001',
        ])->assertOk();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'clock_in_accuracy' => 12.5,
            'device_id' => 'device-test-001',
        ]);
    }

    public function test_clock_in_rejects_compromised_devices_and_keeps_only_a_hash_of_integrity_token(): void
    {
        Storage::fake('private');
        Carbon::setTestNow('2026-09-18 09:00:00');
        $user = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);

        $basePayload = [
            'latitude' => -6.2, 'longitude' => 106.8,
            'photo' => UploadedFile::fake()->image('device.jpg'), 'reason' => 'Traffic',
            'gps_accuracy' => 10, 'device_id' => 'device-integrity',
        ];

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/clock-in', $basePayload + [
            'is_rooted' => true,
        ])->assertUnprocessable();

        $token = 'sensitive-attestation-token';
        $this->actingAs($user, 'sanctum')->post('/api/v1/attendance/clock-in', $basePayload + [
            'device_platform' => 'android',
            'integrity_provider' => 'play_integrity',
            'device_integrity_status' => 'verified',
            'integrity_token' => $token,
            'is_rooted' => false,
            'is_emulator' => false,
        ])->assertOk();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'device_platform' => 'android',
            'integrity_provider' => 'play_integrity',
            'device_integrity_status' => 'verified',
            'device_integrity_token_hash' => hash('sha256', $token),
        ]);
    }

    public function test_clock_in_requires_device_id_and_accurate_gps(): void
    {
        Storage::fake('private');
        Carbon::setTestNow('2026-09-18 09:00:00');
        $user = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.2, 'longitude' => 106.8,
            'photo' => UploadedFile::fake()->image('missing-metadata.jpg'), 'reason' => 'Traffic',
        ])->assertUnprocessable()->assertJsonValidationErrors(['gps_accuracy', 'device_id']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.2, 'longitude' => 106.8,
            'photo' => UploadedFile::fake()->image('poor-gps.jpg'), 'reason' => 'Traffic',
            'gps_accuracy' => 51, 'device_id' => 'device-poor-gps',
        ])->assertUnprocessable()->assertJsonValidationErrors(['gps_accuracy']);
    }

    public function test_clock_in_and_out_store_private_photos_and_require_early_leave_reason(): void
    {
        Storage::fake('private');
        Carbon::setTestNow('2026-09-18 09:00:00');
        $user = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        OfficeLocation::create(['name' => 'Head Office', 'latitude' => -6.20000000, 'longitude' => 106.81666600, 'radius' => 500, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')->post('/api/v1/attendance/clock-in', [
            'latitude' => -6.20010000, 'longitude' => 106.81670000,
            'photo' => UploadedFile::fake()->image('in.jpg'), 'reason' => 'Traffic',
            'gps_accuracy' => 10, 'device_id' => 'device-attendance',
        ])->assertOk();

        $this->actingAs($user, 'sanctum')->post('/api/v1/attendance/clock-out', [
            'latitude' => -6.20010000, 'longitude' => 106.81670000,
            'photo' => UploadedFile::fake()->image('out.jpg'),
            'gps_accuracy' => 10, 'device_id' => 'device-attendance',
        ])->assertUnprocessable()->assertJsonPath('is_early_leave', true);

        $this->actingAs($user, 'sanctum')->post('/api/v1/attendance/clock-out', [
            'latitude' => -6.20010000, 'longitude' => 106.81670000,
            'photo' => UploadedFile::fake()->image('out.jpg'), 'reason' => 'Personal appointment',
            'gps_accuracy' => 10, 'device_id' => 'device-attendance',
        ])->assertOk();

        $attendance = Attendance::where('user_id', $user->id)->firstOrFail();
        Storage::disk('private')->assertExists($attendance->clock_in_photo);
        Storage::disk('private')->assertExists($attendance->clock_out_photo);
    }
}
