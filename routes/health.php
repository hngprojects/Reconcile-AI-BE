<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

Route::get('/health', function () {
    $checks = [
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'services' => []
    ];

    // Database check
    try {
        DB::connection()->getPdo();
        $checks['services']['database'] = 'healthy';
    } catch (Exception $e) {
        $checks['services']['database'] = 'unhealthy';
        $checks['status'] = 'error';
    }

    // Redis check
    try {
        Redis::ping();
        $checks['services']['redis'] = 'healthy';
    } catch (Exception $e) {
        $checks['services']['redis'] = 'unhealthy';
        $checks['status'] = 'error';
    }

    $statusCode = $checks['status'] === 'ok' ? 200 : 503;
    
    return response()->json($checks, $statusCode);
});