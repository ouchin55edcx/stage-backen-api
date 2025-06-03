<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeclarationController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\StatisticsController;
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

// Test email route (remove in production)
Route::get('/test-email', function() {
    try {
        // Clear config cache to ensure we're using the latest settings
        \Artisan::call('config:clear');

        // Log the current mail configuration
        \Log::info('Mail config:', [
            'driver' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username'),
            'from_address' => config('mail.from.address'),
        ]);

        // Send a test email
        \Illuminate\Support\Facades\Mail::to('test@example.com')
            ->send(new \App\Mail\EmployerCredentials('Test User', 'test@example.com', 'testpassword123'));

        return response()->json([
            'message' => 'Email sent successfully',
            'mail_config' => [
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
            ]
        ]);
    } catch (\Exception $e) {
        \Log::error('Mail error: ' . $e->getMessage());
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'mail_config' => [
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
            ]
        ], 500);
    }
});

// Mailtrap test route (remove in production)
Route::get('/test-mailtrap', function() {
    try {
        // Send a test email using Laravel's Mail facade
        \Illuminate\Support\Facades\Mail::to('test@example.com')
            ->send(new \App\Mail\EmployerCredentials(
                'Test User',
                'test@example.com',
                'testpassword123'
            ));

        return response()->json([
            'message' => 'Test email sent to Mailtrap',
            'mail_config' => [
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'from_address' => config('mail.from.address'),
            ]
        ]);
    } catch (\Exception $e) {
        \Log::error('Mailtrap test error: ' . $e->getMessage());
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'mail_config' => [
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'from_address' => config('mail.from.address'),
            ]
        ], 500);
    }
});

// Test employer creation route (remove in production)
Route::post('/test-create-employer', function(Illuminate\Http\Request $request) {
    try {
        // Get the employer controller
        $controller = new \App\Http\Controllers\EmployerController();

        // Create a test request with required data
        $testRequest = new \Illuminate\Http\Request([
            'full_name' => $request->input('full_name', 'Test Employer'),
            'email' => $request->input('email', 'test.employer'.time().'@example.com'),
            'poste' => $request->input('poste', 'Test Position'),
            'phone' => $request->input('phone', '123456789'),
            'service_id' => $request->input('service_id', 1), // Make sure this service exists
        ]);

        // Call the store method
        $response = $controller->store($testRequest);

        // Return the response
        return $response;
    } catch (\Exception $e) {
        \Log::error('Test employer creation error: ' . $e->getMessage());
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Test route for debugging
Route::get('/test-my-declarations', [DeclarationController::class, 'getByEmployer']);



// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Admin routes
    Route::middleware('role:Admin')->group(function () {
        // Service routes
        Route::apiResource('services', ServiceController::class);
        Route::post('services/search', [ServiceController::class, 'search']);

        // Employer management routes
        Route::apiResource('employers', EmployerController::class)->except(['destroy']);
        Route::post('employers/search', [EmployerController::class, 'search']);
        Route::patch('employers/{id}/toggle-active', [EmployerController::class, 'toggleActive']);
        Route::get('employers/{id}/test-email', [EmployerController::class, 'testEmail']);

        // Equipment routes
        Route::apiResource('equipments', EquipmentController::class);
        Route::apiResource('interventions', InterventionController::class);
        Route::apiResource('licenses', LicenseController::class);
        Route::apiResource('maintenances', MaintenanceController::class);

        // Admin declaration routes
        Route::get('all-declarations', [DeclarationController::class, 'getAllDeclarations'])->name('admin.all-declarations');
        Route::get('declarations/{id}', [DeclarationController::class, 'show']);
        Route::get('employers/{employerId}/declarations', [DeclarationController::class, 'getByEmployer']);
        Route::post('declarations/{id}/process', [DeclarationController::class, 'processDeclaration'])->name('admin.process-declaration');

        // Admin statistics routes
        Route::get('statistics', [StatisticsController::class, 'getAdminStatistics'])->name('admin.statistics');
    });

    // Declaration routes accessible by both Employers and Admins
    Route::apiResource('declarations', DeclarationController::class);
    Route::get('/my-declarations', [DeclarationController::class, 'getByEmployer'])->name('my-declarations');

    // Employer-only routes
    Route::middleware('role:Employer')->group(function () {
        // Employer statistics route
        Route::get('/my-statistics', [StatisticsController::class, 'getEmployerStatistics'])->name('employer.statistics');
    });
});
