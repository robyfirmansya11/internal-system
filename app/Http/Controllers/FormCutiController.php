<?php

namespace App\Http\Controllers;

use App\Models\FormCuti;
use Barryvdh\DomPDF\Facade\Pdf;

class FormCutiController extends Controller
{
    public function print($id)
    {
        $cuti = FormCuti::with(['user','department','approver'])->findOrFail($id);

        $pdf = Pdf::loadView(
            'filament.pages.cuti.cuti-pdf',
            compact('cuti')
        )->setPaper('a4','landscape');

        return $pdf->stream('form-cuti.pdf');
    }
}
