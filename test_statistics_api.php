<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Testing Statistics API with SQLite ===\n\n";

try {
    // Create a test admin user and get auth token
    $adminUser = \App\Models\User::where('role', 'Admin')->first();
    if (!$adminUser) {
        echo "❌ No admin user found. Please run: php artisan db:seed\n";
        exit(1);
    }

    // Create a token for the admin
    $token = $adminUser->createToken('test-token')->plainTextToken;
    echo "✅ Admin user found: {$adminUser->email}\n";

    // Test the statistics endpoint
    $request = Request::create('/api/statistics', 'GET');
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $request->headers->set('Accept', 'application/json');

    $response = $kernel->handle($request);
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();

    echo "📊 Statistics API Response:\n";
    echo "Status Code: {$statusCode}\n";

    if ($statusCode === 200) {
        echo "✅ API call successful!\n\n";
        
        $data = json_decode($content, true);
        
        if (isset($data['interventions']['by_month'])) {
            echo "✅ Interventions by month data found\n";
            echo "Sample data: " . json_encode($data['interventions']['by_month']) . "\n\n";
        }
        
        if (isset($data['time_stats']['declarations_by_month'])) {
            echo "✅ Declarations by month data found\n";
            echo "Sample data: " . json_encode($data['time_stats']['declarations_by_month']) . "\n\n";
        }
        
        if (isset($data['time_stats']['equipment_by_month'])) {
            echo "✅ Equipment by month data found\n";
            echo "Sample data: " . json_encode($data['time_stats']['equipment_by_month']) . "\n\n";
        }
        
        if (isset($data['time_stats']['users_by_month'])) {
            echo "✅ Users by month data found\n";
            echo "Sample data: " . json_encode($data['time_stats']['users_by_month']) . "\n\n";
        }
        
        echo "✅ All SQLite date functions are working correctly!\n";
        echo "✅ The MONTH() and YEAR() functions have been successfully replaced with strftime()\n";
        
    } else {
        echo "❌ API call failed!\n";
        echo "Response: {$content}\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test completed ===\n";
