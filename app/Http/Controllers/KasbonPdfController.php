<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kasbon;
use Barryvdh\DomPDF\Facade\Pdf;

class KasbonPdfController extends Controller
{
    public function stream($id)
    {
        $record = Kasbon::with([
            'user',
            'department',
            'company',
            'approver',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.kasbon-pdf', [
            'record' => $record,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('kasbon-' . $record->id . '.pdf');
    }
}
