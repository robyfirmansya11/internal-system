<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\SuratPerintahBayarReport;
use App\Models\Company;
use App\Models\Department;
use App\Models\SuratPerintahBayar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentApplicationLetterReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_applies_date_month_year_and_company_filters_together(): void
    {
        $company = Company::create(['nama' => 'Alpha', 'kode' => 'ALP']);
        $otherCompany = Company::create(['nama' => 'Beta', 'kode' => 'BET']);
        $department = Department::create(['nama_department' => 'Finance']);
        $user = User::factory()->create(['level' => Role::Admin, 'jabatan' => 'HRD']);

        $included = $this->payment($company, $department, $user, '2026-09-15', 'Approved', 1500000);
        $this->payment($company, $department, $user, '2026-08-15', 'Approved', 2000000);
        $this->payment($otherCompany, $department, $user, '2026-09-15', 'Approved', 3000000);

        $report = app(SuratPerintahBayarReport::class);
        $report->filters = [
            'date_from' => '2026-09-01',
            'date_until' => '2026-09-30',
            'month' => 9,
            'year' => 2026,
            'company_id' => $company->id,
        ];

        $query = $this->baseQuery($report);
        $this->assertSame([$included->id], $query->pluck('id')->all());
        $this->assertSame(1500000.0, (float) $this->stats($report)['total']);
        $this->assertSame(1500000.0, (float) $this->stats($report)['approved']);
        $this->assertSame(1, $this->stats($report)['count']);
    }

    private function baseQuery(SuratPerintahBayarReport $report)
    {
        $method = new \ReflectionMethod($report, 'getBaseQuery');

        return $method->invoke($report);
    }

    private function stats(SuratPerintahBayarReport $report): array
    {
        $method = new \ReflectionMethod($report, 'getStats');

        return $method->invoke($report);
    }

    private function payment(Company $company, Department $department, User $user, string $date, string $status, float $total): SuratPerintahBayar
    {
        return SuratPerintahBayar::create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'user_id' => $user->id,
            'tanggal_penagihan' => $date,
            'tanggal_jatuhtempo' => $date,
            'no_invoice' => fake()->unique()->bothify('INV-###'),
            'jumlah' => $total,
            'jumlah_total' => $total,
            'status' => $status,
            'approval_level' => 3,
        ]);
    }
}
