<?php

namespace App\Http\Controllers;

use App\Models\MealClaim;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class MealClaimPdfController extends Controller
{
    public function stream(MealClaim $mealClaim)
    {
        $record = $mealClaim->load([
            'user.profile.atasan',
            'company',
            'department',
            'items',
            'receiptReceiver',
            'verifier',
            'approver',
            'payer',
            'rejector',
            'cancelledBy',
        ]);

        /** @var User $viewer */
        $viewer = auth()->user();

        abort_unless($viewer && (
            $viewer->id === $record->user_id
            || $viewer->isAdmin()
            || $viewer->isSuperadmin()
            || $viewer->isFinanceManager()
            || $viewer->isFinanceAccountingTaxMember()
            || $viewer->isAtasanOf($record->user)
        ), 403);

        return Pdf::loadView('pdf.meal-claim', compact('record'))
            ->setPaper('a4', 'portrait')
            ->stream('Meal-Claim-'.$record->id.'.pdf');
    }
}
