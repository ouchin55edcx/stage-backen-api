<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\EquipmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Admin routes
    Route::middleware('role:Admin')->group(function () {
        // Service routes
        Route::apiResource('services', ServiceController::class);
        Route::post('services/search', [ServiceController::class, 'search']);

        // Employer management routes
        Route::apiResource('employers', EmployerController::class)->except(['destroy']);
        Route::post('employers/search', [EmployerController::class, 'search']);
        Route::patch('employers/{id}/toggle-active', [EmployerController::class, 'toggleActive']);

        // Equipment routes
        Route::apiResource('equipments', EquipmentController::class);
    });

    // Employer routes
    Route::middleware('role:Employer')->group(function () {
        // Employer-specific routes will go here
    });
});
