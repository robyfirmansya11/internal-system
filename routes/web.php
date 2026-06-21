<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SuratPerintahBayarPdfController;
use App\Http\Controllers\OvertimeReportController;
use App\Http\Controllers\PerjalananDinasPdfController;
use App\Http\Controllers\FormCutiController;
use App\Http\Controllers\PermohonanStempelPdfController;
use App\Http\Controllers\KasbonPdfController;
use App\Http\Controllers\LateWorkingReportController;



Route::get('/', function () {
    return view('welcome');
});

// -------------------------------------------------------
// SURAT PERINTAH BAYAR PDF
// -------------------------------------------------------

Route::middleware(['auth'])->group(function () {
    Route::get('/surat-perintah-bayar/{id}/pdf', [SuratPerintahBayarPdfController::class, 'download'])
        ->name('spb.pdf');
});


Route::get('/overtime-report/pdf', [OvertimeReportController::class, 'exportPdf'])
    ->name('overtime.report.pdf');

    // -------------------------------------------------------
// PERJALANAN DINAS PDF
// -------------------------------------------------------

Route::middleware(['auth'])->get('/perjalanan-dinas/{id}/pdf',
    [App\Http\Controllers\PerjalananDinasPdfController::class, 'stream']
)->name('perjalanan-dinas.pdf');

Route::get(
    '/perjalanan-dinas/{record}/export-pdf',
    [PerjalananDinasPdfController::class, 'export']
)->name('perjalanan-dinas.export-pdf');

// -------------------------------------------------------
// CUTI PDF
// -------------------------------------------------------

Route::get('/cuti/print/{id}', [FormCutiController::class, 'print'])
    ->name('cuti.print');

// -------------------------------------------------------
// PERMOHONAN STEMPEL PDF
// -------------------------------------------------------

Route::get('/permohonan-stempel/print/{id}',
    [App\Http\Controllers\PermohonanStempelPdfController::class, 'print']
)->name('permohonan-stempel.print');


// -------------------------------------------------------
// KASBON PDF
// -------------------------------------------------------

Route::middleware(['auth'])->get('/kasbon/{id}/pdf',
    [App\Http\Controllers\KasbonPdfController::class, 'stream']
)->name('kasbon.pdf');

// -------------------------------------------------------
// LATE WORKING REPORT PDF
// -------------------------------------------------------
Route::middleware(['auth'])->get('/late-working/report/pdf',
    [LateWorkingReportController::class, 'exportPdf'])
    ->name('late-working.report.pdf');
