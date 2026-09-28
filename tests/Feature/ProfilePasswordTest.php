<?php
namespace Tests\Feature;
use App\Enums\Role;
use App\Models\User;
use App\Filament\Pages\MyProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;
class ProfilePasswordTest extends TestCase {
    use RefreshDatabase;
    protected function beforeRefreshingDatabase(): void {
        if(config('database.connections.'.config('database.default').'.database')!=='insys_db_testing') throw new \RuntimeException('Isolated test database required.');
    }
    public function test_profile_changes_password_used_by_mobile_login(): void {
        $user=User::factory()->create(['level'=>Role::User,'password'=>'OldTestPassword123!']);
        $this->actingAs($user);
        Livewire::test(MyProfile::class)->set('data.password','NewTestPassword456!')->call('save')->assertHasNoErrors()->assertSet('data.password',null);
        $this->assertTrue(Hash::check('NewTestPassword456!',$user->fresh()->password));
        $this->assertFalse(Hash::check('OldTestPassword123!',$user->fresh()->password));
        $this->postJson('/api/v1/login',['email'=>$user->email,'password'=>'OldTestPassword123!'])->assertUnauthorized();
        $this->postJson('/api/v1/login',['email'=>$user->email,'password'=>'NewTestPassword456!'])->assertOk()->assertJsonStructure(['token','user']);
    }
    public function test_blank_password_preserves_existing_hash(): void {
        $user=User::factory()->create(['level'=>Role::User]);$hash=$user->password;$this->actingAs($user);
        Livewire::test(MyProfile::class)->set('data.password','')->call('save')->assertHasNoErrors();
        $this->assertSame($hash,$user->fresh()->password);
    }
    public function test_short_password_is_rejected_without_changing_hash(): void {
        $user=User::factory()->create(['level'=>Role::User]);$hash=$user->password;$this->actingAs($user);
        Livewire::test(MyProfile::class)->set('data.password','short')->call('save')->assertHasErrors(['data.password']);
        $this->assertSame($hash,$user->fresh()->password);
    }
}
