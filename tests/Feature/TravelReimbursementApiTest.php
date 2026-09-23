<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\PerjalananDinas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TravelReimbursementApiTest extends TestCase
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
        return ['company_id'=>Company::firstOrCreate(['kode'=>'TRAVELAPI'],['nama'=>'Travel Test'])->id,
            'keterangan'=>'Perjalanan kerja','jumlah_lampiran'=>0,'catatan'=>'Test','details'=>[$this->detail()]];
    }
    private function detail():array {
        return ['tanggal_berangkat'=>'2026-09-22','tanggal_tujuan'=>'2026-09-23','waktu_berangkat'=>'08:00','waktu_tujuan'=>'09:00',
            'tempat_berangkat'=>'Kantor','tempat_tujuan'=>'Site','jumlah_hari'=>2,'lama_hotel'=>1,
            'amount_transportasi'=>'100.25','amount_tunjangan'=>'50.10','amount_hotel'=>'200.00','misc'=>'10.00','amount_other'=>'5.05'];
    }
    public function test_auth_and_computed_header_detail_fields():void {
        $this->getJson('/api/v1/travel-reimbursements')->assertUnauthorized();
        $user=$this->applicant();Sanctum::actingAs($user);$data=$this->payload();
        $data['total']=1;$data['terbilang']='Forged';$data['user_id']=999;$data['status']='Approved';
        $data['details'][0]['subtotal']=1;$data['details'][0]['perjalanan_dinas_id']=999;$data['details'][0]['id']=999;
        $r=$this->postJson('/api/v1/travel-reimbursements',$data)->assertCreated()
            ->assertJsonPath('data.total','415.50')->assertJsonPath('data.details.0.subtotal','415.50')
            ->assertJsonPath('data.status','Pending Approval')->assertJsonPath('data.approval_level',2);
        $record=PerjalananDinas::findOrFail($r->json('data.id'));
        $this->assertSame($user->id,$record->user_id);$this->assertSame('Four Hundred Fifteen Rupiah and Fifty Sen',$record->terbilang);
        $this->assertNotSame(999,$record->details->first()->id);
    }
    public function test_workflow_matches_executing_filament_rules():void {
        $boss=$this->applicant(Role::Superuser,'Manager');
        $staff=$this->applicant();EmployeeProfile::create(['user_id'=>$staff->id,'atasan_id'=>$boss->id]);
        $manager=$this->applicant(Role::Superuser,'Manager');EmployeeProfile::create(['user_id'=>$manager->id,'atasan_id'=>$boss->id]);
        $finance=$this->applicant(Role::Superuser,'Finance Manager');EmployeeProfile::create(['user_id'=>$finance->id,'atasan_id'=>$boss->id]);
        $noBoss=$this->applicant();
        foreach([[$staff,1],[$manager,1],[$finance,3],[$noBoss,2]] as [$user,$level]){
            Sanctum::actingAs($user);$this->postJson('/api/v1/travel-reimbursements',$this->payload())->assertCreated()
                ->assertJsonPath('data.approval_level',$level)->assertJsonPath('data.status',$level===3?'Approved':'Pending Approval');
        }
        $this->assertSame(2,$finance->notifications()->count());
    }
    public function test_one_to_five_details_and_update_replaces_without_duplicates():void {
        Sanctum::actingAs($this->applicant());$data=$this->payload();$data['details']=array_fill(0,5,$this->detail());
        $r=$this->postJson('/api/v1/travel-reimbursements',$data)->assertCreated()->assertJsonCount(5,'data.details')->assertJsonPath('data.total','2077.50');
        $id=$r->json('data.id');
        $this->postJson('/api/v1/travel-reimbursements/'.$id,$this->payload())->assertOk()->assertJsonCount(1,'data.details')->assertJsonPath('data.total','415.50');
        $this->assertDatabaseCount('perjalanan_dinas_details',1);
        $data['details']=[];$this->postJson('/api/v1/travel-reimbursements',$data)->assertUnprocessable();
        $data['details']=array_fill(0,6,$this->detail());$this->postJson('/api/v1/travel-reimbursements',$data)->assertUnprocessable();
    }
    public function test_validation_rejects_dates_times_amounts_and_preserves_old_details():void {
        Sanctum::actingAs($this->applicant());$id=$this->postJson('/api/v1/travel-reimbursements',$this->payload())->assertCreated()->json('data.id');
        foreach([['tanggal_tujuan'=>'2026-09-21'],['waktu_berangkat'=>'25:00'],['tanggal_tujuan'=>'2026-09-22','waktu_tujuan'=>'07:00'],
            ['jumlah_hari'=>0],['lama_hotel'=>-1],['amount_tunjangan'=>'1.001'],['amount_transportasi'=>'-1'],['tempat_tujuan'=>''],
            ['jumlah_hari'=>3650,'amount_tunjangan'=>'999999999.99']] as $invalid){
            $data=$this->payload();$data['details'][0]=array_merge($this->detail(),$invalid);
            $this->postJson('/api/v1/travel-reimbursements/'.$id,$data)->assertUnprocessable();
        }
        $this->assertDatabaseCount('perjalanan_dinas_details',1);
        $this->assertSame('415.50',PerjalananDinas::findOrFail($id)->total);
    }
    public function test_ownership_and_final_states():void {
        $owner=$this->applicant();Sanctum::actingAs($owner);$id=$this->postJson('/api/v1/travel-reimbursements',$this->payload())->assertCreated()->json('data.id');
        Sanctum::actingAs($this->applicant());$this->getJson('/api/v1/travel-reimbursements')->assertJsonPath('data',[]);
        $this->getJson('/api/v1/travel-reimbursements/'.$id)->assertNotFound();
        $this->postJson('/api/v1/travel-reimbursements/'.$id,$this->payload())->assertNotFound();
        $this->postJson('/api/v1/travel-reimbursements/'.$id.'/cancel')->assertNotFound();
        Sanctum::actingAs($owner);$this->postJson('/api/v1/travel-reimbursements/'.$id.'/cancel')->assertOk()->assertJsonPath('data.status','Cancelled');
        foreach(['Approved','Rejected','Cancelled'] as $status){
            PerjalananDinas::findOrFail($id)->update(['status'=>$status]);
            $this->postJson('/api/v1/travel-reimbursements/'.$id,$this->payload())->assertUnprocessable();
            $this->postJson('/api/v1/travel-reimbursements/'.$id.'/cancel')->assertUnprocessable();
        }
    }
    public function test_department_and_active_company_required():void {
        Sanctum::actingAs(User::factory()->create());$this->postJson('/api/v1/travel-reimbursements',$this->payload())->assertUnprocessable();
        Sanctum::actingAs($this->applicant());$data=$this->payload();Company::findOrFail($data['company_id'])->delete();
        $this->postJson('/api/v1/travel-reimbursements',$data)->assertUnprocessable()->assertJsonValidationErrors('company_id');
        $this->getJson('/api/v1/travel-reimbursements/companies')->assertOk()->assertExactJson([]);
        $this->assertDatabaseCount('perjalanan_dinas',0);$this->assertDatabaseCount('perjalanan_dinas_details',0);
    }
}
