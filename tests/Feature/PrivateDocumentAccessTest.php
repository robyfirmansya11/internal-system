<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unrelated_user_cannot_download_another_users_ktp(): void
    {
        Storage::fake('private');
        $owner = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $other = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $owner->document()->create(['ktp_file' => 'documents/ktp/private-id.pdf']);
        Storage::disk('private')->put('documents/ktp/private-id.pdf', 'private content');

        $this->actingAs($other)
            ->get(route('private.user-document', [$owner, 'ktp_file']))
            ->assertForbidden();
    }

    public function test_document_owner_can_download_their_own_private_file(): void
    {
        Storage::fake('private');
        $owner = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $owner->document()->create(['ktp_file' => 'documents/ktp/private-id.pdf']);
        Storage::disk('private')->put('documents/ktp/private-id.pdf', 'private content');

        $this->actingAs($owner)
            ->get(route('private.user-document', [$owner, 'ktp_file']))
            ->assertOk();
    }
}
