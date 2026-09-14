<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DecreeController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PermissionController;
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

    Route::prefix('grades')->group(function (): void {
        Route::get('/', [GradeController::class, 'index']);
    });

    Route::prefix('institutions')->group(function (): void {
        Route::get('/', [InstitutionController::class, 'index']);
        Route::post('/', [InstitutionController::class, 'create']);
        Route::get('/{id}', [InstitutionController::class, 'show']);
        Route::post('/{id}', [InstitutionController::class, 'update']);
        Route::delete('/{id}', [InstitutionController::class, 'delete']);
    });

    Route::prefix('decrees')->group(function (): void {
        Route::get('/', [DecreeController::class, 'index']);
    });

    Route::prefix('groups')->group(function (): void {
        Route::get('/', [GroupController::class, 'index']);
    });

    Route::prefix('permissions')->group(function (): void {
        Route::get('/', [PermissionController::class, 'index']);
    });
});
