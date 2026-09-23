<?php

namespace Tests\Feature;

use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MigrateSensitiveFilesToPrivateTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_copy_files_and_real_run_copies_without_deleting_public_source(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        $user = User::factory()->create();
        EmployeeDocument::create(['user_id' => $user->id, 'ktp_file' => 'documents/ktp/legacy.pdf']);
        Storage::disk('public')->put('documents/ktp/legacy.pdf', 'private document');

        $this->artisan('files:migrate-sensitive-to-private --dry-run')->assertSuccessful();
        Storage::disk('private')->assertMissing('documents/ktp/legacy.pdf');

        $this->artisan('files:migrate-sensitive-to-private')->assertSuccessful();
        Storage::disk('private')->assertExists('documents/ktp/legacy.pdf');
        Storage::disk('public')->assertExists('documents/ktp/legacy.pdf');

        $this->artisan('files:migrate-sensitive-to-private --delete-public')->assertSuccessful();
        Storage::disk('private')->assertExists('documents/ktp/legacy.pdf');
        Storage::disk('public')->assertMissing('documents/ktp/legacy.pdf');
    }
}
