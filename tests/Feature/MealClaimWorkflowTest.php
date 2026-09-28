<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Models\MealClaim;
use App\Models\MealClaimItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MealClaimWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipts_recalculate_the_claim_total(): void
    {
        [$claim] = $this->makeClaim();

        MealClaimItem::create($this->itemData($claim, 18000));
        MealClaimItem::create($this->itemData($claim, 22000));

        $claim->refresh();

        $this->assertSame('40000.00', $claim->total_amount);
        $this->assertSame(2, $claim->receipt_count);
    }

    public function test_claim_admin_finance_and_payment_workflow_is_recorded(): void
    {
        [$claim, $owner] = $this->makeClaim();
        MealClaimItem::create($this->itemData($claim, 25000));
        $claimAdmin = User::factory()->create(['level' => Role::Admin, 'jabatan' => 'Claim Admin']);
        $financeManager = User::factory()->create(['level' => Role::Superuser, 'jabatan' => 'Finance Manager']);
        $financeManager->departments()->attach(Department::create(['nama_department' => 'Finance, Accounting & Tax Department'])->id);

        $this->assertTrue($claim->receiveReceipts($claimAdmin));
        $this->assertTrue($claim->verify($claimAdmin, 'Original receipt matches photo.'));
        $this->assertTrue($claim->approve($financeManager));
        $this->assertTrue($claim->markPaid($financeManager, 'TRF-001'));

        $claim->refresh();
        $this->assertSame('Paid', $claim->status);
        $this->assertSame($claimAdmin->id, $claim->verified_by);
        $this->assertSame($financeManager->id, $claim->approved_by);
        $this->assertSame($financeManager->id, $claim->paid_by);
        $this->assertSame($owner->id, $claim->user_id);
        $this->assertDatabaseCount('approval_histories', 4);
    }

    public function test_unrelated_user_cannot_open_a_private_receipt(): void
    {
        Storage::fake('private');
        [$claim, $owner] = $this->makeClaim();
        $item = MealClaimItem::create($this->itemData($claim, 15000));
        $other = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        Storage::disk('private')->put($item->receipt_path, 'private receipt');

        $this->actingAs($other)
            ->get(route('private.meal-claim-receipt', $item))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('private.meal-claim-receipt', $item))
            ->assertOk();
    }

    public function test_owner_can_stream_a_meal_claim_pdf_and_unrelated_user_is_forbidden(): void
    {
        [$claim, $owner] = $this->makeClaim();
        MealClaimItem::create($this->itemData($claim, 15000));
        $other = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);

        $this->actingAs($owner)
            ->get(route('meal-claim.pdf', $claim))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($other)
            ->get(route('meal-claim.pdf', $claim))
            ->assertForbidden();
    }

    public function test_only_a_finance_accounting_tax_department_member_can_mark_a_claim_as_paid(): void
    {
        [$claim] = $this->makeClaim();
        $claim->update(['status' => 'Approved']);
        $financeStaff = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);
        $financeAccountingTax = Department::create(['nama_department' => 'Finance, Accounting & Tax Department']);

        $this->assertFalse($financeStaff->isFinanceAccountingTaxMember());
        $this->assertFalse($claim->canBePaidBy($financeStaff));

        $financeStaff->departments()->attach($financeAccountingTax->id);
        $this->assertTrue($financeStaff->fresh()->isFinanceAccountingTaxMember());
        $this->assertTrue($claim->canBePaidBy($financeStaff->fresh()));
    }

    /** @return array{MealClaim, User} */
    private function makeClaim(): array
    {
        $company = Company::create(['nama' => 'Test Company', 'kode' => 'TST']);
        $department = Department::create(['nama_department' => 'Test Department']);
        $owner = User::factory()->create(['level' => Role::User, 'jabatan' => 'Staff']);

        $claim = MealClaim::create([
            'user_id' => $owner->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'claim_date' => now(),
            'status' => 'Submitted',
        ]);

        return [$claim, $owner];
    }

    private function itemData(MealClaim $claim, int $amount): array
    {
        return [
            'meal_claim_id' => $claim->id,
            'meal_date' => now(),
            'meal_type' => 'Lunch',
            'merchant' => 'Test Restaurant',
            'amount' => $amount,
            'receipt_path' => 'meal-claims/receipts/test-'.$amount.'.jpg',
        ];
    }
}
