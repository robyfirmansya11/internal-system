<?php

namespace App\Http\Controllers;

use App\Models\SuratPerintahBayar;
use Barryvdh\DomPDF\Facade\Pdf;

class SuratPerintahBayarPdfController extends Controller
{
    use Concerns\AuthorizesDocumentAccess;

    public function download($id)
    {
        $record = SuratPerintahBayar::with([
            'company',
            'user',
            'department',
            'approver',
            'approverManager',
            'rejector',
            'cancelledBy',
            'paidBy',        // ← relasi baru
        ])->findOrFail($id);

        $this->authorizeDocumentAccess($record);

        $pdf = Pdf::loadView('pdf.surat-perintah-bayar', compact('record'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('SPB-'.$record->no_invoice.'.pdf');
    }
}
