<?php

namespace App\Http\Controllers;

use App\Models\NotaPenggantianBiaya;
use Barryvdh\DomPDF\Facade\Pdf;

class NotaPenggantianBiayaPdfController extends Controller
{
    use Concerns\AuthorizesDocumentAccess;

    public function stream(NotaPenggantianBiaya $notaPenggantianBiaya)
    {
        $record = $notaPenggantianBiaya->load([
            'company', 'user.profile.atasan', 'department', 'details', 'approverManager',
            'approver', 'rejector', 'cancelledBy',
        ]);

        $this->authorizeDocumentAccess($record);

        return Pdf::loadView('pdf.nota-penggantian-biaya', compact('record'))
            ->setPaper('a4', 'portrait')
            ->stream('Nota-Penggantian-Biaya-'.$record->id.'.pdf');
    }
}
