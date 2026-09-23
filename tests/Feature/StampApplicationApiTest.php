<?php
namespace Tests\Feature;
use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\PermohonanStempel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StampApplicationApiTest extends TestCase {
    use RefreshDatabase;
    protected function beforeRefreshingDatabase(): void {
        if (config('database.connections.'.config('database.default').'.database') !== 'insys_db_testing') {
            throw new \RuntimeException('Tests require isolated insys_db_testing database.');
        }
    }
    protected function setUp(): void { parent::setUp(); Storage::fake('public'); }
    private function user(Role $role = Role::User): User {
        $user = User::factory()->create(['level'=>$role,'jabatan'=>'Manager']);
        $user->departments()->attach(Department::firstOrCreate(['nama_department'=>'Letter API Test']));
        return $user;
    }
    private function payload(): array {
        return ['company_id'=>Company::firstOrCreate(['kode'=>'LETTERAPI'],['nama'=>'Letter Test'])->id,
            'tanggal'=>'2026-09-22','nomor_surat'=>'001/BMU-LGL/IX/2026','tujuan'=>'Penerima Test','keterangan'=>'Test','ditandatangani_oleh'=>'Director',
            'lampiran'=>UploadedFile::fake()->create('letter.pdf',10,'application/pdf')];
    }
    private function createLetter(): PermohonanStempel {
        $response = $this->postJson('/api/v1/stamp-applications',$this->payload())->assertCreated();
        return PermohonanStempel::findOrFail($response->json('data.id'));
    }

    public function test_auth_and_auto_approval_with_server_owned_fields(): void {
        $this->getJson('/api/v1/stamp-applications')->assertUnauthorized();
        $user=$this->user(); Sanctum::actingAs($user);
        $response=$this->postJson('/api/v1/stamp-applications',array_merge($this->payload(),['user_id'=>999,'status'=>'Cancelled','approval_level'=>0]))
          ->assertCreated()->assertJsonPath('data.status','Approved')->assertJsonPath('data.can_cancel',false);
        $record=PermohonanStempel::findOrFail($response->json('data.id'));
        $this->assertSame($user->id,$record->user_id);
        $this->assertSame($user->id,$record->approved_by);
        Storage::disk('public')->assertExists($record->lampiran);
    }
    public function test_staff_and_manager_with_boss_wait_for_approval(): void {
        $boss=$this->user();
        foreach (['Staff','Manager','Finance Manager'] as $title) {
            $user=$this->user(); $user->update(['jabatan'=>$title]);
            \App\Models\EmployeeProfile::create(['user_id'=>$user->id,'atasan_id'=>$boss->id]);
            Sanctum::actingAs($user);
            $this->postJson('/api/v1/stamp-applications',$this->payload())->assertCreated()
                ->assertJsonPath('data.status',$title==='Finance Manager'?'Approved':'Pending Approval');
        }
        $this->assertDatabaseCount('notifications',2);
    }
    public function test_missing_manager_or_department_rejected(): void {
        $user=$this->user(); $user->update(['jabatan'=>'Staff']); Sanctum::actingAs($user);
        $this->postJson('/api/v1/stamp-applications',$this->payload())->assertUnprocessable();
        \App\Models\EmployeeProfile::create(['user_id'=>$user->id,'atasan_id'=>$user->id]);
        $user->unsetRelation('profile');
        $this->postJson('/api/v1/stamp-applications',$this->payload())->assertUnprocessable();
        Sanctum::actingAs(User::factory()->create(['jabatan'=>'Manager']));
        $this->postJson('/api/v1/stamp-applications',$this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('permohonan_stempels',0);
        $this->assertCount(0,Storage::disk('public')->allFiles());
    }
    public function test_owner_edit_preserves_file_and_cancellation_locks_form(): void {
        $user=$this->user(); $boss=$this->user();
        \App\Models\EmployeeProfile::create(['user_id'=>$user->id,'atasan_id'=>$boss->id]);
        Sanctum::actingAs($user); $record=$this->createLetter(); $path=$record->lampiran;
        $data=$this->payload(); unset($data['lampiran']); $data['tujuan']='Updated'; $data['tanggal_surat']='2026-09-20';
        $this->postJson('/api/v1/stamp-applications/'.$record->id,$data)->assertOk()->assertJsonPath('data.tujuan','Updated');
        $this->assertSame($path,$record->fresh()->lampiran);
        $data['tanggal_surat']='';
        $this->postJson('/api/v1/stamp-applications/'.$record->id,$data)->assertOk()->assertJsonPath('data.tanggal_surat',null);
        $this->postJson('/api/v1/stamp-applications/'.$record->id.'/cancel')->assertOk()->assertJsonPath('data.status','Cancelled');
        $this->postJson('/api/v1/stamp-applications/'.$record->id,$data)->assertUnprocessable();
        $this->postJson('/api/v1/stamp-applications/'.$record->id.'/cancel')->assertUnprocessable();
    }
    public function test_ownership_and_terminal_status_protection(): void {
        $owner=$this->user(); Sanctum::actingAs($owner); $r=$this->createLetter();
        Sanctum::actingAs($this->user());
        $this->getJson('/api/v1/stamp-applications')->assertJsonPath('data',[]);
        $this->getJson('/api/v1/stamp-applications/'.$r->id)->assertNotFound();
        $this->postJson('/api/v1/stamp-applications/'.$r->id,$this->payload())->assertNotFound();
        $this->postJson('/api/v1/stamp-applications/'.$r->id.'/cancel')->assertNotFound();
        Sanctum::actingAs($owner);
        foreach (['Approved','Rejected','Cancelled'] as $status) {
            $r->update(['status'=>$status]);
            $this->postJson('/api/v1/stamp-applications/'.$r->id,$this->payload())->assertUnprocessable();
            $this->postJson('/api/v1/stamp-applications/'.$r->id.'/cancel')->assertUnprocessable();
        }
    }
    public function test_invalid_fields_and_files_rejected(): void {
        Sanctum::actingAs($this->user());
        foreach ([['ditandatangani_oleh'=>''],['tanggal'=>'invalid'],['tujuan'=>''],['lampiran'=>null],
            ['lampiran'=>UploadedFile::fake()->create('bad.php',1,'text/plain')],
            ['lampiran'=>UploadedFile::fake()->create('large.pdf',10241,'application/pdf')]] as $invalid) {
            $this->postJson('/api/v1/stamp-applications',array_merge($this->payload(),$invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('permohonan_stempels',0);
    }
}
