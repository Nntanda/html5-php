<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Testing Laravel Bootstrap...\n";

try {
    // Test basic Laravel functionality
    echo "✓ Laravel bootstrapped successfully\n";
    
    // Test if we can load models
    $memberClass = new ReflectionClass('App\Models\Member');
    echo "✓ Member model loaded\n";
    
    $loanClass = new ReflectionClass('App\Models\Loan');
    echo "✓ Loan model loaded\n";
    
    $loanServiceClass = new ReflectionClass('App\Services\LoanService');
    echo "✓ LoanService loaded\n";
    
    // Test if we can instantiate controllers
    $memberController = new ReflectionClass('App\Http\Controllers\MemberController');
    echo "✓ MemberController loaded\n";
    
    $loanController = new ReflectionClass('App\Http\Controllers\LoanController');
    echo "✓ LoanController loaded\n";
    
    echo "\n✅ All classes loaded successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}