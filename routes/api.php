<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CutiApprovalController;
use App\Http\Controllers\Api\CutiController;
use App\Http\Controllers\Api\OvertimeController;
use App\Http\Controllers\Api\LatePermitController;
use App\Http\Controllers\Api\PaymentApplicationController;
use App\Http\Controllers\Api\RegisterLetterController;
use App\Http\Controllers\Api\StampApplicationController;
use App\Http\Controllers\Api\LoanNoteController;
use App\Http\Controllers\Api\TravelReimbursementController;
use App\Http\Controllers\Api\ExpenseReimbursementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Absensi Mobile App
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public — tidak perlu token
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-login');

    // Protected — butuh Bearer token Sanctum
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::prefix('attendance')->group(function () {
            Route::get('/today', [AttendanceController::class, 'today']);
            Route::post('/clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('/clock-out', [AttendanceController::class, 'clockOut']);
            Route::get('/history', [AttendanceController::class, 'history']);
            Route::get('/office-location', [AttendanceController::class, 'officeLocation']);
        });

        Route::prefix('overtime')->group(function () {
            Route::get('/approvals', [\App\Http\Controllers\Api\OvertimeApprovalController::class, 'index']);
            Route::post('/{id}/approve', [\App\Http\Controllers\Api\OvertimeApprovalController::class, 'approve']);
            Route::post('/{id}/reject', [\App\Http\Controllers\Api\OvertimeApprovalController::class, 'reject']);
            Route::get('/', [OvertimeController::class, 'index']);
            Route::post('/', [OvertimeController::class, 'store']);
            Route::get('/{id}', [OvertimeController::class, 'show']);
            Route::post('/{id}/cancel', [OvertimeController::class, 'cancel']);
        });

        Route::prefix('late-working-permits')->group(function () {
            Route::get('/approvals', [\App\Http\Controllers\Api\LatePermitApprovalController::class, 'index']);
            Route::post('/{id}/approve', [\App\Http\Controllers\Api\LatePermitApprovalController::class, 'approve']);
            Route::post('/{id}/reject', [\App\Http\Controllers\Api\LatePermitApprovalController::class, 'reject']);
            Route::get('/', [LatePermitController::class, 'index']);
            Route::post('/', [LatePermitController::class, 'store']);
            Route::get('/{id}', [LatePermitController::class, 'show']);
            Route::post('/{id}/cancel', [LatePermitController::class, 'cancel']);
        });

        Route::prefix('payment-applications')->group(function () {
            Route::get('/approvals', [\App\Http\Controllers\Api\PaymentApprovalController::class, 'index']);
            Route::post('/{id}/approve', [\App\Http\Controllers\Api\PaymentApprovalController::class, 'approve']);
            Route::post('/{id}/reject', [\App\Http\Controllers\Api\PaymentApprovalController::class, 'reject']);
            Route::get('/companies', [PaymentApplicationController::class, 'companies']);
            Route::get('/', [PaymentApplicationController::class, 'index']);
            Route::post('/', [PaymentApplicationController::class, 'store']);
            Route::get('/{id}', [PaymentApplicationController::class, 'show']);
            Route::post('/{id}/cancel', [PaymentApplicationController::class, 'cancel']);
        });
        Route::prefix('register-letters')->group(function () {
            Route::get('/companies', [RegisterLetterController::class, 'companies']);
            Route::get('/', [RegisterLetterController::class, 'index']);
            Route::post('/', [RegisterLetterController::class, 'store']);
            Route::get('/{id}', [RegisterLetterController::class, 'show']);
            Route::post('/{id}', [RegisterLetterController::class, 'update']);
            Route::delete('/{id}', [RegisterLetterController::class, 'destroy']);
        });
        Route::prefix('stamp-applications')->group(function () {
            Route::get('/approvals', [\App\Http\Controllers\Api\StampApprovalController::class, 'index']);
            Route::post('/{id}/approve', [\App\Http\Controllers\Api\StampApprovalController::class, 'approve']);
            Route::post('/{id}/reject', [\App\Http\Controllers\Api\StampApprovalController::class, 'reject']);
            Route::get('/companies', [StampApplicationController::class, 'companies']);
            Route::get('/', [StampApplicationController::class, 'index']);
            Route::post('/', [StampApplicationController::class, 'store']);
            Route::get('/{id}', [StampApplicationController::class, 'show']);
            Route::post('/{id}', [StampApplicationController::class, 'update']);
            Route::post('/{id}/cancel', [StampApplicationController::class, 'cancel']);
        });
        Route::prefix('loan-notes')->group(function () {
            Route::get('/approvals', [\App\Http\Controllers\Api\LoanApprovalController::class, 'index']);
            Route::post('/{id}/approve', [\App\Http\Controllers\Api\LoanApprovalController::class, 'approve']);
            Route::post('/{id}/reject', [\App\Http\Controllers\Api\LoanApprovalController::class, 'reject']);
            Route::get('/companies', [LoanNoteController::class, 'companies']);
            Route::get('/', [LoanNoteController::class, 'index']);
            Route::post('/', [LoanNoteController::class, 'store']);
            Route::get('/{id}', [LoanNoteController::class, 'show']);
            Route::post('/{id}', [LoanNoteController::class, 'update']);
            Route::post('/{id}/cancel', [LoanNoteController::class, 'cancel']);
        });
        Route::prefix('travel-reimbursements')->group(function () {
            Route::get('/approvals', [\App\Http\Controllers\Api\TravelApprovalController::class, 'index']);
            Route::post('/{id}/approve', [\App\Http\Controllers\Api\TravelApprovalController::class, 'approve']);
            Route::post('/{id}/reject', [\App\Http\Controllers\Api\TravelApprovalController::class, 'reject']);
            Route::get('/companies', [TravelReimbursementController::class, 'companies']);
            Route::get('/', [TravelReimbursementController::class, 'index']);
            Route::post('/', [TravelReimbursementController::class, 'store']);
            Route::get('/{id}', [TravelReimbursementController::class, 'show']);
            Route::post('/{id}', [TravelReimbursementController::class, 'update']);
            Route::post('/{id}/cancel', [TravelReimbursementController::class, 'cancel']);
        });
        Route::prefix('expense-reimbursements')->group(function () {
            Route::get('/approvals', [\App\Http\Controllers\Api\ExpenseApprovalController::class, 'index']);
            Route::post('/{id}/approve', [\App\Http\Controllers\Api\ExpenseApprovalController::class, 'approve']);
            Route::post('/{id}/reject', [\App\Http\Controllers\Api\ExpenseApprovalController::class, 'reject']);
            Route::get('/companies', [ExpenseReimbursementController::class, 'companies']);
            Route::get('/', [ExpenseReimbursementController::class, 'index']);
            Route::post('/', [ExpenseReimbursementController::class, 'store']);
            Route::get('/{id}', [ExpenseReimbursementController::class, 'show']);
            Route::post('/{id}', [ExpenseReimbursementController::class, 'update']);
            Route::post('/{id}/cancel', [ExpenseReimbursementController::class, 'cancel']);
        });
        // Cuti
        Route::prefix('cuti')->group(function () {
            Route::get('/', [CutiController::class, 'index']);
            Route::post('/', [CutiController::class, 'store']);
            Route::get('/quota', [CutiController::class, 'quota']);
            Route::get('/approvals', [CutiApprovalController::class, 'index']);   // ⬅️ TAMBAHAN — sebelum /{id}
            Route::get('/{id}', [CutiController::class, 'show']);
            Route::post('/{id}/cancel', [CutiController::class, 'cancel']);
            Route::post('/{id}/approve', [CutiApprovalController::class, 'approve']); // ⬅️ TAMBAHAN
            Route::post('/{id}/reject', [CutiApprovalController::class, 'reject']);   // ⬅️ TAMBAHAN
        });

    });

});
