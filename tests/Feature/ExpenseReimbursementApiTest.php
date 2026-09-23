<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\NotaPenggantianBiaya;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpenseReimbursementApiTest extends TestCase
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

    private function payload():array {
        return ['company_id'=>Company::firstOrCreate(['kode'=>'EXPAPI'],['nama'=>'Expense Test'])->id,
            'tanggal'=>'2026-09-22','jumlah_lampiran'=>0,'informasi_transfer'=>'Rekening uji',
            'details'=>[['keterangan'=>'Transport','jumlah'=>'100.25'],['keterangan'=>'Makan','jumlah'=>'50.10']]];
    }
    public function test_auth_and_server_owned_totals_details():void {
        $this->getJson('/api/v1/expense-reimbursements')->assertUnauthorized();
        $user=$this->applicant();Sanctum::actingAs($user);$data=$this->payload();
        $data['jumlah_total']=1;$data['terbilang']='Forged';$data['user_id']=999;$data['status']='Approved';
        $data['details'][0]['nota_penggantian_biaya_id']=999;$data['details'][0]['id']=999;
        $response=$this->postJson('/api/v1/expense-reimbursements',$data)->assertCreated()->assertJsonPath('data.jumlah_total','150.35')
            ->assertJsonPath('data.status','Pending Approval')->assertJsonPath('data.approval_level',2)->assertJsonPath('data.has_attachment',false);
        $r=NotaPenggantianBiaya::findOrFail($response->json('data.id'));
        $this->assertSame($user->id,$r->user_id);$this->assertSame('150.35',$r->jumlah);
        $this->assertSame('One Hundred Fifty Rupiah and Thirty-five Sen',$r->terbilang);
        $this->assertNotSame(999,$r->details->first()->id);
    }
    public function test_staff_manager_finance_workflows():void {
        $boss=$this->applicant(Role::Superuser,'Manager');
        $staff=$this->applicant();EmployeeProfile::create(['user_id'=>$staff->id,'atasan_id'=>$boss->id]);
        $manager=$this->applicant(Role::Superuser,'Manager');EmployeeProfile::create(['user_id'=>$manager->id,'atasan_id'=>$boss->id]);
        $finance=$this->applicant(Role::Superuser,'Finance Manager');EmployeeProfile::create(['user_id'=>$finance->id,'atasan_id'=>$boss->id]);
        foreach([[$staff,1],[$manager,2],[$finance,3],[$this->applicant(),2]] as [$user,$level]){
            Sanctum::actingAs($user);$this->postJson('/api/v1/expense-reimbursements',$this->payload())->assertCreated()
                ->assertJsonPath('data.approval_level',$level)->assertJsonPath('data.status',$level===3?'Approved':'Pending Approval');
        }
        $this->assertDatabaseCount('notifications',4);
    }
    public function test_edit_replaces_details_preserves_file_and_recalculates():void {
        Sanctum::actingAs($this->applicant());$data=$this->payload();$data['lampiran']=UploadedFile::fake()->create('receipt.pdf',10,'application/pdf');
        $response=$this->postJson('/api/v1/expense-reimbursements',$data)->assertCreated();$id=$response->json('data.id');
        $path=NotaPenggantianBiaya::findOrFail($id)->lampiran;Storage::disk('public')->assertExists($path);
        $data=$this->payload();$data['details']=[['keterangan'=>'Updated','jumlah'=>'15.01']];
        $this->postJson('/api/v1/expense-reimbursements/'.$id,$data)->assertOk()->assertJsonPath('data.jumlah_total','15.01')->assertJsonCount(1,'data.details');
        $this->assertSame($path,NotaPenggantianBiaya::findOrFail($id)->lampiran);
        $this->assertDatabaseCount('nota_penggantian_biaya_details',1);
        $data['details'][0]['jumlah']='-1';$this->postJson('/api/v1/expense-reimbursements/'.$id,$data)->assertUnprocessable();
        $this->assertSame('15.01',NotaPenggantianBiaya::findOrFail($id)->jumlah_total);
    }
    public function test_validation_and_limits():void {
        Sanctum::actingAs($this->applicant());
        foreach([[],array_fill(0,6,['keterangan'=>'A','jumlah'=>'1']),[['keterangan'=>'','jumlah'=>'1']],
            [['keterangan'=>'A','jumlah'=>'1.001']],[['keterangan'=>'A','jumlah'=>'1e3']],
            array_fill(0,2,['keterangan'=>'A','jumlah'=>'999999999999.99'])] as $details){
            $this->postJson('/api/v1/expense-reimbursements',array_merge($this->payload(),['details'=>$details]))->assertUnprocessable();
        }
        foreach([['tanggal'=>'invalid'],['jumlah_lampiran'=>-1],['lampiran'=>UploadedFile::fake()->create('bad.php',1,'text/plain')],
            ['lampiran'=>UploadedFile::fake()->create('big.pdf',10241,'application/pdf')]] as $invalid){
            $this->postJson('/api/v1/expense-reimbursements',array_merge($this->payload(),$invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('nota_penggantian_biaya',0);$this->assertCount(0,Storage::disk('public')->allFiles());
        $this->postJson('/api/v1/expense-reimbursements',array_merge($this->payload(),['details'=>array_fill(0,5,['keterangan'=>'A','jumlah'=>'0.01'])]))
            ->assertCreated()->assertJsonCount(5,'data.details')->assertJsonPath('data.jumlah_total','0.05');
    }
    public function test_ownership_and_terminal_states():void {
        $owner=$this->applicant();Sanctum::actingAs($owner);$id=$this->postJson('/api/v1/expense-reimbursements',$this->payload())->assertCreated()->json('data.id');
        Sanctum::actingAs($this->applicant());$this->getJson('/api/v1/expense-reimbursements')->assertJsonPath('data',[]);
        $this->getJson('/api/v1/expense-reimbursements/'.$id)->assertNotFound();
        $this->postJson('/api/v1/expense-reimbursements/'.$id,$this->payload())->assertNotFound();
        $this->postJson('/api/v1/expense-reimbursements/'.$id.'/cancel')->assertNotFound();
        Sanctum::actingAs($owner);$this->postJson('/api/v1/expense-reimbursements/'.$id.'/cancel')->assertOk()->assertJsonPath('data.status','Cancelled');
        foreach(['Approved','Rejected','Cancelled'] as $status){
            NotaPenggantianBiaya::findOrFail($id)->update(['status'=>$status]);
            $this->postJson('/api/v1/expense-reimbursements/'.$id,$this->payload())->assertUnprocessable();
            $this->postJson('/api/v1/expense-reimbursements/'.$id.'/cancel')->assertUnprocessable();
        }
    }
    public function test_department_and_active_company_required():void {
        Sanctum::actingAs(User::factory()->create());$this->postJson('/api/v1/expense-reimbursements',$this->payload())->assertUnprocessable();
        Sanctum::actingAs($this->applicant());$data=$this->payload();Company::findOrFail($data['company_id'])->delete();
        $this->postJson('/api/v1/expense-reimbursements',$data)->assertUnprocessable()->assertJsonValidationErrors('company_id');
        $this->getJson('/api/v1/expense-reimbursements/companies')->assertOk()->assertExactJson([]);
    }
}
