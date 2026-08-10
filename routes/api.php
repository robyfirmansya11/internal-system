<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CutiApprovalController;
use App\Http\Controllers\Api\CutiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Absensi Mobile App
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public — tidak perlu token
    Route::post('/login', [AuthController::class, 'login']);

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
