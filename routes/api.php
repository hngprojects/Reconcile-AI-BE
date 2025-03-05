<?php

use App\Http\Controllers\V1\WaitListController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return 'Welcome to Reconcile AI  Version 1';
    });
    Route::post('/wait-list', [WaitListController::class, 'store']);
});
