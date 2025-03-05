<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\WaitListController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\Api\V1\Auth\SignUpController;

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return 'Welcome to ReconcileAI API v1.';
    });

    Route::prefix('auth')->middleware('guest')->name('auth.')->group(function () {
        Route::post('/sign-up', [SignUpController::class, 'store']);
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        // Route::post('/register', [AuthController::class, 'register'])->name('register');
        // Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
        // Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
        // Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('verify-email');
        // Route::post('/resend-verification-email', [AuthController::class, 'resendVerificationEmail'])->name('resend-verification-email');
        // Route::post('/logout', [AuthController::class, 'logout'])->withoutMiddleware('guest')->middleware('auth:sanctum')->name('logout');
    });
  
    Route::post('/reconcile', [ReconciliationController::class, 'reconcile']);
    Route::post('/wait-list', [WaitListController::class, 'store']);
});
