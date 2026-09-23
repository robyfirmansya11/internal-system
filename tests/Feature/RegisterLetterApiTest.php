<?php
namespace Tests\Feature;
use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\RegisterSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegisterLetterApiTest extends TestCase {
    use RefreshDatabase;
    protected function beforeRefreshingDatabase(): void {
        if (config('database.connections.'.config('database.default').'.database') !== 'insys_db_testing') {
            throw new \RuntimeException('Tests require isolated insys_db_testing database.');
        }
    }
    protected function setUp(): void { parent::setUp(); Storage::fake('public'); }
    private function user(Role $role = Role::User): User {
        $user = User::factory()->create(['level'=>$role]);
        $user->departments()->attach(Department::firstOrCreate(['nama_department'=>'Letter API Test']));
        return $user;
    }
    private function payload(): array {
        return ['company_id'=>Company::firstOrCreate(['kode'=>'LETTERAPI'],['nama'=>'Letter Test'])->id,
            'tanggal_surat'=>'2026-09-22','no_surat'=>'001/BMU-LGL/IX/2026','ditujukan'=>'Penerima Test','keterangan'=>'Test',
            'lampiran_surat'=>UploadedFile::fake()->create('letter.pdf',10,'application/pdf')];
    }
    private function createLetter(): RegisterSurat {
        $response = $this->postJson('/api/v1/register-letters',$this->payload())->assertCreated();
        return RegisterSurat::findOrFail($response->json('data.id'));
    }
    public function test_authentication_and_creation_ignores_forged_ownership(): void {
        $this->getJson('/api/v1/register-letters')->assertUnauthorized();
        $this->getJson('/api/v1/register-letters/companies')->assertUnauthorized();
        $this->postJson('/api/v1/register-letters',$this->payload())->assertUnauthorized();
        $user=$this->user(); Sanctum::actingAs($user);
        $response=$this->postJson('/api/v1/register-letters',array_merge($this->payload(),['user_id'=>999,'department_id'=>999]))
            ->assertCreated()->assertJsonPath('data.can_edit',true)->assertJsonPath('data.can_delete',true)->assertJsonPath('data.has_attachment',true);
        $record=RegisterSurat::findOrFail($response->json('data.id'));
        $this->assertSame($user->id,$record->user_id);
        Storage::disk('public')->assertExists($record->lampiran_surat);
        $this->getJson('/api/v1/register-letters/companies')->assertOk()->assertJsonFragment(['nama'=>'Letter Test']);
    }
    public function test_all_users_can_read_but_only_permitted_users_can_modify(): void {
        Sanctum::actingAs($this->user()); $record=$this->createLetter();
        $owner=$record->user_id;
        Sanctum::actingAs($this->user());
        $this->getJson('/api/v1/register-letters')->assertOk()->assertJsonPath('data.0.id',$record->id);
        $this->getJson('/api/v1/register-letters/'.$record->id)->assertOk()->assertJsonPath('can_edit',false)->assertJsonPath('can_delete',false);
        $this->postJson('/api/v1/register-letters/'.$record->id,$this->payload())->assertForbidden();
        $this->deleteJson('/api/v1/register-letters/'.$record->id)->assertForbidden();
        foreach ([Role::Admin,Role::Superuser] as $role) {
            Sanctum::actingAs($this->user($role));
            $this->postJson('/api/v1/register-letters/'.$record->id,array_merge($this->payload(),['user_id'=>999]))->assertOk();
            $this->deleteJson('/api/v1/register-letters/'.$record->id)->assertForbidden();
        }
        $this->assertSame($owner,$record->fresh()->user_id);
    }
    public function test_edit_preserves_attachment_or_replaces_with_unique_file(): void {
        Sanctum::actingAs($this->user()); $record=$this->createLetter(); $old=$record->lampiran_surat;
        $data=$this->payload(); unset($data['lampiran_surat']); $data['ditujukan']='Updated';
        $this->postJson('/api/v1/register-letters/'.$record->id,$data)->assertOk()->assertJsonPath('data.ditujukan','Updated');
        $this->assertSame($old,$record->fresh()->lampiran_surat);
        $this->postJson('/api/v1/register-letters/'.$record->id,$this->payload())->assertOk();
        $this->assertNotSame($old,$record->fresh()->lampiran_surat);
        Storage::disk('public')->assertExists($old);
        Storage::disk('public')->assertExists($record->fresh()->lampiran_surat);
    }
    public function test_duplicate_number_rejected_even_after_soft_delete(): void {
        Sanctum::actingAs($this->user()); $record=$this->createLetter();
        $this->postJson('/api/v1/register-letters',$this->payload())->assertUnprocessable()->assertJsonValidationErrors('no_surat');
        $this->deleteJson('/api/v1/register-letters/'.$record->id)->assertOk();
        $this->assertSoftDeleted($record);
        $this->getJson('/api/v1/register-letters')->assertJsonPath('data',[]);
        $this->getJson('/api/v1/register-letters/'.$record->id)->assertNotFound();
        $this->postJson('/api/v1/register-letters',$this->payload())->assertUnprocessable()->assertJsonValidationErrors('no_surat');
        $this->assertCount(1,Storage::disk('public')->allFiles());
    }
    public function test_invalid_input_rejected_before_upload(): void {
        Sanctum::actingAs($this->user());
        foreach ([['tanggal_surat'=>'invalid'],['ditujukan'=>''],['no_surat'=>''],['lampiran_surat'=>null],
            ['lampiran_surat'=>UploadedFile::fake()->create('bad.php',1,'text/plain')],
            ['lampiran_surat'=>UploadedFile::fake()->create('large.pdf',10241,'application/pdf')]] as $invalid) {
            $this->postJson('/api/v1/register-letters',array_merge($this->payload(),$invalid))->assertUnprocessable();
        }
        $payload=$this->payload(); Company::findOrFail($payload['company_id'])->delete();
        $this->postJson('/api/v1/register-letters',$payload)->assertUnprocessable()->assertJsonValidationErrors('company_id');
        $this->assertDatabaseCount('register_surats',0);
        $this->assertCount(0,Storage::disk('public')->allFiles());
    }
    public function test_missing_department_rejected(): void {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/register-letters',$this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('register_surats',0);
    }
}
