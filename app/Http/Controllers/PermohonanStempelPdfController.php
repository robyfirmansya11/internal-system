<?php

namespace App\Http\Controllers;

use App\Models\PermohonanStempel;
use Barryvdh\DomPDF\Facade\Pdf;

class PermohonanStempelPdfController extends Controller
{
    public function print($id)
    {
        $record = PermohonanStempel::with([
            'user',
            'department',
            'company',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.permohonan-stempel', [
            'record' => $record,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('permohonan-stempel-' . $record->id . '.pdf');
    }
}