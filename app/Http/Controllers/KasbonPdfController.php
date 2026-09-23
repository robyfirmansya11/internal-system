<?php

namespace App\Http\Controllers;

use App\Models\Kasbon;
use Barryvdh\DomPDF\Facade\Pdf;

class KasbonPdfController extends Controller
{
    use Concerns\AuthorizesDocumentAccess;

    public function stream($id)
    {
        $record = Kasbon::with([
            'user',
            'department',
            'company',
            'approver.departments',        // Finance Manager
            'approverManager.departments', // Atasan level 1
            'rejector',
            'cancelledBy',
        ])->findOrFail($id);

        $this->authorizeDocumentAccess($record);

        $pdf = Pdf::loadView('pdf.kasbon-pdf', [
            'record' => $record,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('kasbon-'.$record->id.'.pdf');
    }
}
