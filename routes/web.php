<?php

use App\Http\Controllers\FormCutiController;
use App\Http\Controllers\LateWorkingReportController;
use App\Http\Controllers\OvertimeReportController;
use App\Http\Controllers\PerjalananDinasPdfController;
use App\Http\Controllers\SuratPerintahBayarPdfController;
use App\Http\Controllers\NotaPenggantianBiayaPdfController;
use App\Http\Controllers\PrivateDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

// -------------------------------------------------------
// SURAT PERINTAH BAYAR PDF
// -------------------------------------------------------

Route::middleware(['auth'])->group(function () {
    Route::get('/private-documents/users/{user}/{field}', [PrivateDocumentController::class, 'userDocument'])->name('private.user-document');
    Route::get('/private-documents/cuti/{cuti}', [PrivateDocumentController::class, 'leaveAttachment'])->name('private.leave-attachment');
    Route::get('/private-documents/attendance/{attendance}/{field}', [PrivateDocumentController::class, 'attendancePhoto'])->name('private.attendance-photo');
    Route::get('/surat-perintah-bayar/{id}/pdf', [SuratPerintahBayarPdfController::class, 'download'])
        ->name('spb.pdf');
    Route::get('/nota-penggantian-biaya/{notaPenggantianBiaya}/pdf', [NotaPenggantianBiayaPdfController::class, 'stream'])
        ->name('nota-penggantian-biaya.pdf');
});

Route::middleware(['auth'])->get('/overtime-report/pdf', [OvertimeReportController::class, 'exportPdf'])
    ->name('overtime.report.pdf');

Route::middleware(['auth'])->get('/overtime-report/excel', [OvertimeReportController::class, 'exportExcel'])
    ->name('overtime.report.excel');

// -------------------------------------------------------
// PERJALANAN DINAS PDF
// -------------------------------------------------------

Route::middleware(['auth'])->get('/perjalanan-dinas/{id}/pdf',
    [App\Http\Controllers\PerjalananDinasPdfController::class, 'stream']
)->name('perjalanan-dinas.pdf');

Route::middleware(['auth'])->get(
    '/perjalanan-dinas/{record}/export-pdf',
    [PerjalananDinasPdfController::class, 'stream']
)->name('perjalanan-dinas.export-pdf');

// -------------------------------------------------------
// CUTI PDF
// -------------------------------------------------------

Route::middleware(['auth'])->get('/cuti/print/{id}', [FormCutiController::class, 'print'])
    ->name('cuti.print');

// -------------------------------------------------------
// PERMOHONAN STEMPEL PDF
// -------------------------------------------------------

Route::middleware(['auth'])->get('/permohonan-stempel/print/{id}',
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

// Export Absensi Excel
Route::middleware(['auth'])->get(
    '/attendance/export',
    [\App\Http\Controllers\AttendanceExportController::class, 'export']
)->name('attendance.export');
