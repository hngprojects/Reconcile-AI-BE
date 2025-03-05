<?php

use App\Http\Controllers\WaitListController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\Api\V1\Auth\SignUpController;

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return 'Welcome to ReconcileAI API v1.';
    });

    Route::post('/auth/sign-up', [SignUpController::class, 'store']);
    Route::post('/reconcile', [ReconciliationController::class, 'reconcile']);
    Route::post('/wait-list', [WaitListController::class, 'store']);
});
