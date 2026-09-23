<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\RegisterSurats\RegisterSuratResource;
use App\Models\Company;
use App\Models\Department;
use App\Models\RegisterSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterLetterAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_creator_can_delete_or_force_delete_register_letter(): void
    {
        $creator = User::factory()->create(['level' => Role::User]);
        $otherUser = User::factory()->create(['level' => Role::User]);
        $letter = $this->letter($creator);

        $this->actingAs($creator);
        $this->assertTrue(RegisterSuratResource::canDelete($letter));
        $this->assertTrue(RegisterSuratResource::canForceDelete($letter));

        $this->actingAs($otherUser);
        $this->assertFalse(RegisterSuratResource::canDelete($letter));
        $this->assertFalse(RegisterSuratResource::canForceDelete($letter));
    }

    public function test_register_letter_uses_soft_delete_for_recovery(): void
    {
        $letter = $this->letter(User::factory()->create(['level' => Role::User]));
        $letter->delete();

        $this->assertSoftDeleted('register_surats', ['id' => $letter->id]);
        $this->assertNotNull(RegisterSurat::withTrashed()->find($letter->id));
    }

    private function letter(User $creator): RegisterSurat
    {
        $company = Company::create(['nama' => 'Test Company', 'kode' => fake()->unique()->lexify('REG???')]);
        $department = Department::firstOrCreate(['nama_department' => 'Test Department']);

        return RegisterSurat::create([
            'user_id' => $creator->id, 'department_id' => $department->id, 'company_id' => $company->id,
            'no_surat' => fake()->unique()->bothify('REG-####'), 'tanggal_surat' => now()->toDateString(),
            'ditujukan' => 'Test recipient', 'keterangan' => 'Automated test register letter',
        ]);
    }
}
