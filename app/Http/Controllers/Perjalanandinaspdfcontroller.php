<?php

namespace App\Http\Controllers;

use App\Models\PerjalananDinas;
use Barryvdh\DomPDF\Facade\Pdf;



class PerjalananDinasPdfController extends Controller
{
    public function stream($id)
    {
        $record = PerjalananDinas::with([
            'user',
            'department',
            'company',
            'details',
            'approvedBy',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.perjalanan-dinas-pdf', [
            'record'  => $record,
            'details' => $record->details ?? collect(),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('perjalanan-dinas-' . $record->id . '.pdf');
    }


}
