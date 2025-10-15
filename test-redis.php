<?php

// Test Redis connectivity for Reconcile AI Docker setup

echo "Testing Redis Connection...\n";

try {
    // Test with environment variables
    $redisHost = $_ENV['REDIS_HOST'] ?? 'redis';
    $redisPort = $_ENV['REDIS_PORT'] ?? 6379;
    
    echo "Connecting to Redis at {$redisHost}:{$redisPort}\n";
    
    $redis = new Redis();
    $connected = $redis->connect($redisHost, $redisPort, 5); // 5 second timeout
    
    if (!$connected) {
        throw new Exception("Failed to connect to Redis");
    }
    
    // Test basic operations
    $redis->set('test_key', 'test_value');
    $value = $redis->get('test_key');
    
    if ($value !== 'test_value') {
        throw new Exception("Redis read/write test failed");
    }
    
    $redis->del('test_key');
    
    echo "✅ Redis connection successful!\n";
    echo "✅ Redis read/write operations working!\n";
    
    // Test Laravel cache
    if (function_exists('cache')) {
        cache(['laravel_test' => 'working']);
        $laravelTest = cache('laravel_test');
        
        if ($laravelTest === 'working') {
            echo "✅ Laravel cache integration working!\n";
        } else {
            echo "⚠️  Laravel cache integration issue\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Redis connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 All Redis tests passed!\n";