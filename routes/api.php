<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.rate.limit:5,1'])->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
});

Route::middleware(['auth:sanctum', 'role.access'])->group(function (): void {
    Route::delete('logout', [AuthController::class, 'logout']);
    Route::delete('logout-all-devices', [AuthController::class, 'logoutAllDevices']);
    Route::get('active-sessions', [AuthController::class, 'getActiveSessions']);
});
