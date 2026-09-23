<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_be_soft_deleted_and_restored_without_losing_data(): void
    {
        $company = Company::create([
            'nama' => 'Temporary Company',
            'kode' => 'TMP',
            'npwp' => '01.234.567.8-999.000',
            'alamat' => 'Test address',
        ]);

        $company->delete();

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
        $this->assertNull(Company::find($company->id));

        $trashedCompany = Company::onlyTrashed()->findOrFail($company->id);
        $this->assertSame('Temporary Company', $trashedCompany->nama);
        $trashedCompany->restore();

        $this->assertSame('TMP', Company::findOrFail($company->id)->kode);
    }
}
