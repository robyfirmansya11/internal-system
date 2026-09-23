<?php

namespace App\Http\Controllers;

use App\Models\PermohonanStempel;
use Barryvdh\DomPDF\Facade\Pdf;

class PermohonanStempelPdfController extends Controller
{
    use Concerns\AuthorizesDocumentAccess;

    public function print($id)
    {
        $record = PermohonanStempel::with([
            'user',
            'department',
            'company',
            'approver.departments',
            'rejector',
            'cancelledBy',
        ])->findOrFail($id);

        $this->authorizeDocumentAccess($record);

        $pdf = Pdf::loadView('pdf.permohonan-stempel', [
            'record' => $record,
        ])->setPaper('a4', 'portrait');

        // Metadata PDF
        $pdf->getDomPDF()->addInfo('Title', 'Permohonan Stempel');
        $pdf->getDomPDF()->addInfo('Author', 'PT. Artabumi Sentra Industri');
        $pdf->getDomPDF()->addInfo('Subject', 'Permohonan Stempel - Report');
        $pdf->getDomPDF()->addInfo('Creator', 'HRIS');
        $pdf->getDomPDF()->addInfo('Producer', 'Laravel DomPDF');

        return $pdf->stream(
            'Permohonan-Stempel-'.$record->id.'.pdf'
        );
    }
}
