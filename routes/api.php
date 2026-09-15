<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DecreeController;
use App\Http\Controllers\DisciplinaryController;
use App\Http\Controllers\EchelonController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmploymentTypeController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\GradeHistoryController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PositionHistoryController;
use App\Http\Controllers\RecognitionController;
use App\Http\Controllers\RecognitionHistoryController;
use App\Http\Controllers\ResidenceController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TrainingHistoryController;
use App\Http\Controllers\UserController;
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

    Route::get('employees', [EmployeeController::class, 'index']);

    Route::prefix('position-histories')->group(function (): void {
        Route::get('/', [PositionHistoryController::class, 'index']);
        Route::post('/', [PositionHistoryController::class, 'create']);
        Route::get('/{id}', [PositionHistoryController::class, 'show']);
        Route::post('/{id}', [PositionHistoryController::class, 'update']);
        Route::delete('/{id}', [PositionHistoryController::class, 'delete']);
    });

    Route::prefix('grade-histories')->group(function (): void {
        Route::get('/', [GradeHistoryController::class, 'index']);
        Route::post('/', [GradeHistoryController::class, 'create']);
        Route::get('/{id}', [GradeHistoryController::class, 'show']);
        Route::post('/{id}', [GradeHistoryController::class, 'update']);
        Route::delete('/{id}', [GradeHistoryController::class, 'delete']);
    });

    Route::prefix('training-histories')->group(function (): void {
        Route::get('/', [TrainingHistoryController::class, 'index']);
        Route::get('/groups', [TrainingHistoryController::class, 'technicalGroups']);
        Route::post('/', [TrainingHistoryController::class, 'create']);
        Route::get('/{id}', [TrainingHistoryController::class, 'show']);
        Route::post('/{id}', [TrainingHistoryController::class, 'update']);
        Route::delete('/{id}', [TrainingHistoryController::class, 'delete']);

        Route::prefix('levels')->group(function (): void {
            Route::get('/structural', [TrainingHistoryController::class, 'structuralLevels']);
            Route::get('/functional', [TrainingHistoryController::class, 'functionalLevels']);
        });
    });

    Route::prefix('recognition-histories')->group(function (): void {
        Route::get('/', [RecognitionHistoryController::class, 'index']);
        Route::post('/', [RecognitionHistoryController::class, 'create']);
        Route::get('/{id}', [RecognitionHistoryController::class, 'show']);
        Route::post('/{id}', [RecognitionHistoryController::class, 'update']);
        Route::delete('/{id}', [RecognitionHistoryController::class, 'delete']);
    });

    Route::prefix('positions')->group(function (): void {
        Route::get('/', [PositionController::class, 'index']);
        Route::post('/', [PositionController::class, 'create']);
        Route::get('/available-order', [PositionController::class, 'availableOrder']);
        Route::get('/{id}', [PositionController::class, 'show']);
        Route::post('/{id}', [PositionController::class, 'update']);
        Route::delete('/{id}', [PositionController::class, 'delete']);
    });

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

    Route::prefix('employment-types')->group(function (): void {
        Route::get('/', [EmploymentTypeController::class, 'index']);
        Route::post('/', [EmploymentTypeController::class, 'create']);
        Route::get('/{id}', [EmploymentTypeController::class, 'show']);
        Route::post('/{id}', [EmploymentTypeController::class, 'update']);
        Route::delete('/{id}', [EmploymentTypeController::class, 'delete']);
    });

    Route::prefix('decrees')->group(function (): void {
        Route::get('/', [DecreeController::class, 'index']);
    });

    Route::prefix('echelons')->group(function (): void {
        Route::get('/', [EchelonController::class, 'index']);
    });

    Route::prefix('groups')->group(function (): void {
        Route::get('/', [GroupController::class, 'index']);
    });

    Route::prefix('disciplinaries')->group(function (): void {
        Route::get('/', [DisciplinaryController::class, 'index']);
    });

    Route::prefix('residences')->group(function (): void {
        Route::get('/', [ResidenceController::class, 'index']);
    });

    Route::prefix('recognitions')->group(function (): void {
        Route::get('/', [RecognitionController::class, 'index']);
    });

    Route::prefix('permissions')->group(function (): void {
        Route::get('/', [PermissionController::class, 'index']);
    });

    Route::prefix('roles')->group(function (): void {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'create']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::post('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'delete']);
    });

    Route::prefix('users')->group(function (): void {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'create']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/{id}', [UserController::class, 'update']);
        Route::put('/status', [UserController::class, 'status']);
    });
});
