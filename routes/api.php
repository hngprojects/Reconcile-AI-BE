<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReconciliationController;

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return 'Welcome to ReconcileAI API v1.';
    });

    Route::post('/reconcile', [ReconciliationController::class, 'reconcile']);
});
