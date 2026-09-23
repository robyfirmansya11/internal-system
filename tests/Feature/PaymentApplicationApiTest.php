<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\SuratPerintahBayar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentApplicationApiTest extends TestCase
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

    private function payload(): array
    {
        $company = Company::firstOrCreate(['kode' => 'PAYAPI'], ['nama' => 'Payment API Test']);
        return [
            'company_id' => $company->id, 'no_invoice' => 'INV-TEST', 'customer' => 'Vendor Test',
            'tanggal_penagihan' => '2026-09-20', 'tanggal_jatuhtempo' => '2026-09-30',
            'jumlah' => '100000.00', 'pph' => '2000.00', 'admin' => '2500.00',
            'pembayaran_tahap' => 1, 'jumlah_lampiran' => 1,
            'keterangan' => 'Pengadaan', 'informasi_transfer' => 'Rekening uji',
            'lampiran' => UploadedFile::fake()->create('invoice.pdf', 10, 'application/pdf'),
        ];
    }

    public function test_authentication_and_company_options(): void
    {
        $this->getJson('/api/v1/payment-applications')->assertUnauthorized();
        $this->getJson('/api/v1/payment-applications/companies')->assertUnauthorized();
        $this->postJson('/api/v1/payment-applications', $this->payload())->assertUnauthorized();
        Sanctum::actingAs($this->applicant());
        $this->getJson('/api/v1/payment-applications/companies')->assertOk()->assertJsonFragment(['nama' => 'Payment API Test']);
    }

    public function test_upload_calculation_and_server_owned_fields(): void
    {
        $user = $this->applicant();
        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/payment-applications', array_merge($this->payload(), [
            'user_id' => 999, 'department_id' => 999, 'status' => 'Paid', 'approval_level' => 3,
            'ppn' => 0, 'jumlah_total' => 1, 'terbilang' => 'Forged', 'paid_by' => 999,
        ]))->assertCreated()->assertJsonPath('data.ppn', 11000)
            ->assertJsonPath('data.jumlah_total', 111500)->assertJsonPath('data.status', 'Pending Approval')
            ->assertJsonPath('data.approval_level', 2)->assertJsonPath('data.has_attachment', true);
        $record = SuratPerintahBayar::findOrFail($response->json('data.id'));
        $this->assertSame($user->id, $record->user_id);
        $this->assertNull($record->paid_by);
        $this->assertNotSame('Forged', $record->terbilang);
        Storage::disk('public')->assertExists($record->lampiran);
    }

    public function test_workflow_for_staff_manager_and_finance_manager(): void
    {
        $manager = $this->applicant(Role::Superuser, 'Manager');
        $staff = $this->applicant();
        EmployeeProfile::create(['user_id' => $staff->id, 'atasan_id' => $manager->id]);
        Sanctum::actingAs($staff);
        $this->postJson('/api/v1/payment-applications', $this->payload())->assertCreated()->assertJsonPath('data.approval_level', 1);
        Sanctum::actingAs($manager);
        $this->postJson('/api/v1/payment-applications', $this->payload())->assertCreated()->assertJsonPath('data.approval_level', 2);
        $finance = $this->applicant(Role::Admin, 'Finance Manager');
        EmployeeProfile::create(['user_id' => $finance->id, 'atasan_id' => $manager->id]);
        Sanctum::actingAs($finance);
        $this->postJson('/api/v1/payment-applications', $this->payload())->assertCreated()
            ->assertJsonPath('data.status', 'Approved')->assertJsonPath('data.approval_level', 3)
            ->assertJsonPath('data.can_cancel', false);
    }

    public function test_invalid_dates_amounts_and_attachment_are_rejected(): void
    {
        Sanctum::actingAs($this->applicant());
        $payload = $this->payload();
        unset($payload['lampiran']);
        $this->postJson('/api/v1/payment-applications', array_merge($payload, [
            'tanggal_jatuhtempo' => '2026-09-19', 'jumlah' => '-1',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['tanggal_jatuhtempo', 'jumlah', 'lampiran']);
        $this->postJson('/api/v1/payment-applications', array_merge($this->payload(), [
            'pph' => '999999.00',
        ]))->assertUnprocessable();
        $this->postJson('/api/v1/payment-applications', array_merge($this->payload(), [
            'lampiran' => UploadedFile::fake()->create('invoice.pdf', 10241, 'application/pdf'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('lampiran');
        $this->postJson('/api/v1/payment-applications', array_merge($this->payload(), [
            'lampiran' => UploadedFile::fake()->create('script.php', 1, 'application/x-httpd-php'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('lampiran');
        $this->assertDatabaseCount('surat_perintah_bayars', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_missing_department_and_deleted_company_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(['level' => Role::User]));
        $this->postJson('/api/v1/payment-applications', $this->payload())->assertUnprocessable();
        Sanctum::actingAs($this->applicant());
        $payload = $this->payload();
        Company::findOrFail($payload['company_id'])->delete();
        $this->postJson('/api/v1/payment-applications', $payload)->assertUnprocessable()->assertJsonValidationErrors('company_id');
        $this->getJson('/api/v1/payment-applications/companies')->assertOk()->assertExactJson([]);
    }

    public function test_ownership_and_terminal_status_cancellation(): void
    {
        $owner = $this->applicant();
        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/v1/payment-applications', $this->payload())->assertCreated()->json('data.id');
        Sanctum::actingAs($this->applicant());
        $this->getJson('/api/v1/payment-applications')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/payment-applications/'.$id)->assertNotFound();
        $this->postJson('/api/v1/payment-applications/'.$id.'/cancel')->assertNotFound();
        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/payment-applications/'.$id)->assertOk();
        $this->postJson('/api/v1/payment-applications/'.$id.'/cancel')->assertOk()->assertJsonPath('data.status', 'Cancelled');
        foreach (['Cancelled', 'Approved', 'Rejected', 'Paid'] as $status) {
            SuratPerintahBayar::findOrFail($id)->update(['status' => $status]);
            $this->postJson('/api/v1/payment-applications/'.$id.'/cancel')->assertUnprocessable();
        }
    }

    public function test_fractional_amount_rounds_consistently(): void
    {
        Sanctum::actingAs($this->applicant());
        $this->postJson('/api/v1/payment-applications', array_merge($this->payload(), [
            'jumlah' => '100.50', 'pph' => '0.01', 'admin' => '0.01',
        ]))->assertCreated()->assertJsonPath('data.ppn', 11)->assertJsonPath('data.jumlah_total', 112);
    }
}
