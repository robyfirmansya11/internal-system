<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Kasbon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoanNoteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.connections.'.config('database.default').'.database') !== 'insys_db_testing') {
            throw new \RuntimeException('Tests require isolated insys_db_testing database.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function applicant(Role $role = Role::User, string $jabatan = 'Staff'): User
    {
        $user = User::factory()->create(['level' => $role, 'jabatan' => $jabatan]);
        $user->departments()->attach(Department::firstOrCreate(['nama_department' => 'API Test']));
        return $user;
    }

    private function payload(): array {
        return ['company_id'=>Company::firstOrCreate(['kode'=>'LOANAPI'],['nama'=>'Loan Test'])->id,
            'tanggal'=>'2026-09-22','jumlah_dana'=>'1500.50','keterangan'=>'Keperluan kerja','informasi_transfer'=>'Rekening uji'];
    }
    public function test_auth_optional_attachment_and_server_amount_in_words(): void {
        $this->getJson('/api/v1/loan-notes')->assertUnauthorized();
        $user=$this->applicant(); Sanctum::actingAs($user);
        $r=$this->postJson('/api/v1/loan-notes',array_merge($this->payload(),['user_id'=>999,'status'=>'Approved','terbilang'=>'Forged']))
            ->assertCreated()->assertJsonPath('data.jumlah_dana','1500.50')->assertJsonPath('data.status','Pending Approval')
            ->assertJsonPath('data.approval_level',2)->assertJsonPath('data.has_attachment',false);
        $record=Kasbon::findOrFail($r->json('data.id'));
        $this->assertSame($user->id,$record->user_id);
        $this->assertSame('One Thousand Five Hundred Rupiah and Fifty Sen',$record->terbilang);
        $this->assertCount(0,Storage::disk('public')->allFiles());
    }
    public function test_staff_manager_finance_and_self_supervisor_workflows(): void {
        $boss=$this->applicant(Role::Superuser,'Manager');
        $staff=$this->applicant(); EmployeeProfile::create(['user_id'=>$staff->id,'atasan_id'=>$boss->id]);
        $manager=$this->applicant(Role::Superuser,'Manager'); EmployeeProfile::create(['user_id'=>$manager->id,'atasan_id'=>$boss->id]);
        $finance=$this->applicant(Role::Superuser,'Finance Manager'); EmployeeProfile::create(['user_id'=>$finance->id,'atasan_id'=>$boss->id]);
        $self=$this->applicant(); EmployeeProfile::create(['user_id'=>$self->id,'atasan_id'=>$self->id]);
        foreach ([[$staff,1],[$manager,2],[$finance,3],[$self,2]] as [$user,$level]) {
            Sanctum::actingAs($user);
            $this->postJson('/api/v1/loan-notes',$this->payload())->assertCreated()
                ->assertJsonPath('data.approval_level',$level)->assertJsonPath('data.status',$level===3?'Approved':'Pending Approval');
        }
        $this->assertDatabaseCount('notifications',4);
    }
    public function test_upload_edit_and_cancel_preserve_history(): void {
        Sanctum::actingAs($this->applicant());
        $data=$this->payload(); $data['lampiran']=UploadedFile::fake()->create('loan.pdf',10,'application/pdf');
        $response=$this->postJson('/api/v1/loan-notes',$data)->assertCreated();
        $r=Kasbon::findOrFail($response->json('data.id')); $path=$r->lampiran;
        Storage::disk('public')->assertExists($path);
        $this->postJson('/api/v1/loan-notes/'.$r->id,array_merge($this->payload(),['jumlah_dana'=>'15.01','terbilang'=>'Forged','status'=>'Approved']))
            ->assertOk()->assertJsonPath('data.jumlah_dana','15.01')->assertJsonPath('data.terbilang','Fifteen Rupiah and One Sen')
            ->assertJsonPath('data.status','Pending Approval');
        $this->assertSame($path,$r->fresh()->lampiran);
        $this->postJson('/api/v1/loan-notes/'.$r->id.'/cancel')->assertOk()->assertJsonPath('data.status','Cancelled');
        $this->assertDatabaseHas('kasbons',['id'=>$r->id,'status'=>'Cancelled']);
    }
    public function test_owner_only_and_terminal_states_locked(): void {
        $owner=$this->applicant(); Sanctum::actingAs($owner);
        $id=$this->postJson('/api/v1/loan-notes',$this->payload())->assertCreated()->json('data.id');
        Sanctum::actingAs($this->applicant());
        $this->getJson('/api/v1/loan-notes')->assertJsonPath('data',[]);
        $this->getJson('/api/v1/loan-notes/'.$id)->assertNotFound();
        $this->postJson('/api/v1/loan-notes/'.$id,$this->payload())->assertNotFound();
        $this->postJson('/api/v1/loan-notes/'.$id.'/cancel')->assertNotFound();
        Sanctum::actingAs($owner);
        foreach (['Approved','Rejected','Cancelled'] as $status) {
            Kasbon::findOrFail($id)->update(['status'=>$status]);
            $this->postJson('/api/v1/loan-notes/'.$id,$this->payload())->assertUnprocessable();
            $this->postJson('/api/v1/loan-notes/'.$id.'/cancel')->assertUnprocessable();
        }
    }
    public function test_invalid_amounts_dates_and_uploads_rejected(): void {
        Sanctum::actingAs($this->applicant());
        foreach (['0','-1','1.001','1e3','1000000000000','1,50'] as $amount) {
            $this->postJson('/api/v1/loan-notes',array_merge($this->payload(),['jumlah_dana'=>$amount]))->assertUnprocessable()->assertJsonValidationErrors('jumlah_dana');
        }
        foreach ([['tanggal'=>'invalid'],['lampiran'=>UploadedFile::fake()->create('bad.php',1,'text/plain')],
            ['lampiran'=>UploadedFile::fake()->create('large.pdf',10241,'application/pdf')]] as $invalid) {
            $this->postJson('/api/v1/loan-notes',array_merge($this->payload(),$invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('kasbons',0);
        $this->assertCount(0,Storage::disk('public')->allFiles());
        $this->postJson('/api/v1/loan-notes',array_merge($this->payload(),['jumlah_dana'=>'999999999999.99']))
            ->assertCreated()->assertJsonPath('data.jumlah_dana','999999999999.99');
    }
    public function test_department_and_active_company_required(): void {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/loan-notes',$this->payload())->assertUnprocessable();
        Sanctum::actingAs($this->applicant()); $data=$this->payload(); Company::findOrFail($data['company_id'])->delete();
        $this->postJson('/api/v1/loan-notes',$data)->assertUnprocessable()->assertJsonValidationErrors('company_id');
        $this->getJson('/api/v1/loan-notes/companies')->assertOk()->assertExactJson([]);
    }
}
