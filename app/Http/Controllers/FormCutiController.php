<?php

namespace App\Http\Controllers;

use App\Models\FormCuti;
use Barryvdh\DomPDF\Facade\Pdf;

class FormCutiController extends Controller
{
    use Concerns\AuthorizesDocumentAccess;

    public function print($id)
    {
        $cuti = FormCuti::with([
            'user',
            'department',
            'approver',
            'manager',   // atasan level 1 (approved_by_manager)
            'financeManager', // Finance Manager (approved_by_finance_manager)
            'hrd',       // HRD level 2 (approved_by_hrd)
            'rejector',  // rejected_by
            'cancelledBy', // cancelled_by
        ])->findOrFail($id);

        $this->authorizeDocumentAccess($cuti);

        $pdf = Pdf::loadView(
            'filament.pages.cuti.cuti-pdf',
            compact('cuti')
        )->setPaper('a4', 'landscape');

        return $pdf->stream('form-cuti.pdf');
    }
}
