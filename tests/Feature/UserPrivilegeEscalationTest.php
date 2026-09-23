<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserPrivilegeEscalationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_assign_the_superadmin_role(): void
    {
        $admin = User::factory()->create(['level' => Role::Admin, 'jabatan' => 'HRD']);
        $target = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $this->actingAs($admin);

        $this->expectException(ValidationException::class);
        $target->update(['level' => Role::Superadmin]);
    }

    public function test_superadmin_can_assign_the_superadmin_role(): void
    {
        $superadmin = User::factory()->create(['level' => Role::Superadmin, 'jabatan' => 'President Director']);
        $target = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $this->actingAs($superadmin);

        $target->update(['level' => Role::Superadmin]);

        $this->assertSame(Role::Superadmin, $target->fresh()->level);
    }
}
