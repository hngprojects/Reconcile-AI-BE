<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\NewsLetterController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\WaitListController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return 'Welcome to ReconcileAI API v1.';
    });

    Route::prefix('auth')->middleware('guest')->name('auth.')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        // Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
        // Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
        // Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('verify-email');
        // Route::post('/resend-verification-email', [AuthController::class, 'resendVerificationEmail'])->name('resend-verification-email');
        Route::post('/logout', [AuthController::class, 'logout'])->withoutMiddleware('guest')->middleware('jwt.auth')->name('logout');
    });

    Route::prefix('newsletter')->name('newsletter.')->group(function () {
        Route::post('/subscribe', [NewsLetterController::class, 'subscribe'])->name('subscribe');
        Route::post('/unsubscribe', [NewsLetterController::class, 'unsubscribe'])->name('unsubscribe');
    });

    Route::prefix('contact')->name('contact.')->group(function () {
        Route::post('/contact-us', [ContactUsController::class, 'saveContactMessage'])->name('contact-us');
    });

    Route::post('/reconcile', [ReconciliationController::class, 'reconcile']);
    Route::post('/wait-list', [WaitListController::class, 'store']);
});
