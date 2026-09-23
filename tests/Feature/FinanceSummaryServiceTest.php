<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\Kasbon;
use App\Models\SuratPerintahBayar;
use App\Models\User;
use App\Services\FinanceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_combines_sources_and_keeps_paid_separate_from_outstanding(): void
    {
        $company = Company::create(['nama' => 'Alpha', 'kode' => 'ALP']);
        $department = Department::create(['nama_department' => 'Finance']);
        $user = User::factory()->create(['level' => Role::Admin, 'jabatan' => 'HRD']);
        SuratPerintahBayar::create(['company_id' => $company->id, 'department_id' => $department->id, 'user_id' => $user->id, 'tanggal_penagihan' => '2026-09-10', 'tanggal_jatuhtempo' => '2026-09-20', 'no_invoice' => 'INV-001', 'jumlah_total' => 1000000, 'status' => 'Approved', 'paid_at' => now()]);
        Kasbon::create(['company_id' => $company->id, 'department_id' => $department->id, 'user_id' => $user->id, 'tanggal' => '2026-09-11', 'keterangan' => 'Advance', 'jumlah_dana' => 500000, 'terbilang' => 'Five Hundred Thousand Rupiah', 'informasi_transfer' => 'Test account', 'status' => 'Pending Approval']);

        $summary = app(FinanceSummaryService::class)->summary(['date_from' => '2026-09-01', 'date_until' => '2026-09-30', 'company_id' => $company->id]);

        $this->assertSame(1500000.0, (float) $summary['total']);
        $this->assertSame(1000000.0, (float) $summary['approved']);
        $this->assertSame(1000000.0, (float) $summary['paid']);
        $this->assertSame(500000.0, (float) $summary['outstanding']);
        $this->assertSame(2, $summary['count']);
    }
}
