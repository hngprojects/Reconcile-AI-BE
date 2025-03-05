<?php

use App\Http\Controllers\WaitListController;
use App\Http\Controllers\Api\V1\Auth\SignUpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// every route should live inside the v1 group
Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return 'Welcome to Reconcile AI  Version 1';
    });

    Route::post('/auth/sign-up', [SignUpController::class, 'store']);

    Route::post('/wait-list', [WaitListController::class, 'store']);
});
