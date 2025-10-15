<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/api/docs');
});

// Include health check routes
require __DIR__.'/health.php';
