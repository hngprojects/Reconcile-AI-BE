<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\OutboundMarketingController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\WaitListController;
use App\Http\Controllers\Api\NewsLetterController;
use App\Http\Controllers\CustomerFeedbackController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PaymentPlanController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Middleware\ThrottleUnauthenticated;

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return 'Welcome to ReconcileAI API v1.';
        // return view('welcome');
    });

    Route::prefix('auth')->middleware('guest')->name('auth.')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/logout', [AuthController::class, 'logout'])->withoutMiddleware('guest')->middleware('jwt.auth')->name('logout');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
        Route::get('/check-token', [AuthController::class, 'checkToken'])
            ->middleware('jwt.auth')
            ->name('check-token');

        // Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('verify-email');
        // Route::post('/resend-verification-email', [AuthController::class, 'resendVerificationEmail'])->name('resend-verification-email');

        // google auth
        Route::get('/google', [GoogleAuthController::class, 'redirectToGoogle']);
        Route::get('/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
    });

    Route::middleware('auth:api')->get('/user', [GoogleAuthController::class, 'fetchUser'])->name('user');
    Route::middleware('auth:api')->put('payment-plan', [PaymentPlanController::class, 'update'])->name('payment-plan');
    Route::middleware('auth:api')->group(function () {
        Route::post('/profile/update', [UserController::class, 'updateProfile']);
    });
    Route::prefix('newsletter')->name('newsletter.')->group(function () {
        Route::get('/unsubscribe/{email}', [NewsLetterController::class, 'oneClickUnsubscribe'])->name('one-click-unsubscribe');
        Route::get('/resubscribe/{email}', [NewsLetterController::class, 'oneClickResubscribe'])->name('resubscribe');
        Route::get('/result', [NewsLetterController::class, 'showResult'])->name('result');

        Route::post('/subscribe', [NewsLetterController::class, 'subscribe'])->name('subscribe');
        Route::post('/unsubscribe', [NewsLetterController::class, 'unsubscribe'])->name('unsubscribe');
    });


    // outbound marketing api
    Route::post('/outbound-marketing', [OutboundMarketingController::class, 'store']);

    // partners
    Route::post('/partners', [PartnerController::class, 'submit'])->name('partners');
    Route::post('/reconcile', [ReconciliationController::class, 'reconcile'])->name('reconcile')->middleware(ThrottleUnauthenticated::class);
    Route::get('/reconciliations/{reconciliation}', [ReconciliationController::class, 'getReconciledRecords'])->whereUuid('reconciliation')->name('reconciled-records');
    Route::get('/reconciliations/{reconciliation}/export', [ReconciliationController::class, 'export'])->name('export');
    Route::middleware('auth:api')->post('/reconcile-embeddings', [ReconciliationController::class, 'testEmbeddings'])->name('embeddings');
    Route::post('/reconcile/{reconciliation}', [ReconciliationController::class, 'matchUnmatch'])->whereUuid('reconciliation')->name('manual-reconciliation');
    Route::post('/wait-list', [WaitListController::class, 'store'])->name('wait-list');
    Route::post('/contact', [ContactController::class, 'contact'])->name('contact');

    // feebback api
    Route::post('/customer-feedback', [CustomerFeedbackController::class, 'store'])->name('customer.feedback');

    // jobs

    Route::post('/job-application', [JobApplicationController::class, 'store']);
});
