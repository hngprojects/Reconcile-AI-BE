<?php

use App\Http\Controllers\ChartOfAccountController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\WaitListController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\LedgerEntryController;
use App\Http\Controllers\PaymentPlanController;
use App\Http\Controllers\AccountSetupController;
use App\Http\Middleware\CheckReconciliationLimit;
use App\Http\Controllers\Api\NewsLetterController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\CustomerFeedbackController;
use App\Http\Controllers\BookkeepingLedgerController;
use App\Http\Controllers\OutboundMarketingController;
use App\Http\Controllers\BillingTransactionController;
use App\Http\Controllers\Api\V1\BusinessInfoController;
use App\Http\Controllers\AccountChartCategoryController;
use App\Http\Controllers\Api\V1\DashboardController;

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
        Route::post('/google-login', [GoogleAuthController::class, 'login']);
        Route::post('/refresh', [GoogleAuthController::class, 'refresh']);
    });

    Route::middleware('auth:api')->get('/user', [GoogleAuthController::class, 'fetchUser'])->name('user');

    Route::middleware('auth:api')->group(function () {
        Route::post('/profile/update', [UserController::class, 'updateProfile'])->name('update-profile');
        Route::delete('/user', [UserController::class, 'deleteAccount'])->name('user.delete');
        // Get current payment plan
        Route::get('payment-plan', [PaymentPlanController::class, 'show'])->name('payment-plan.show');
        // Create new payment plan
        Route::post('payment-plan', [PaymentPlanController::class, 'store'])->name('payment-plan.store');
        // Update payment plan
        Route::put('payment-plan', [PaymentPlanController::class, 'update'])->name('payment-plan.update');
        // Optional: Payment history route if you implement it later
        Route::get('payment-plan/history', [BillingTransactionController::class, 'history'])->name('payment-plan.history');

        Route::post('/ledger-entries/upload', [LedgerEntryController::class, 'uploadLedger']);
        Route::post('/ledger-entries', [LedgerEntryController::class, 'store']);
        Route::get('/ledger-entries', [LedgerEntryController::class, 'index']);
        Route::get('/ledger-entries/{id}', [LedgerEntryController::class, 'show']);
        Route::put('/ledger-entries/{id}', [LedgerEntryController::class, 'update']);

        Route::get('/bookkeeping-ledgers', [BookkeepingLedgerController::class, 'index']);
        Route::post('/bookkeeping-ledgers', [BookkeepingLedgerController::class, 'store']);
        Route::put('/bookkeeping-ledgers/{ledger}/toggle', [BookkeepingLedgerController::class, 'toggle']);

        // bank account
        Route::apiResource('bank-accounts', BankAccountController::class);
        Route::patch('bank-accounts/{bankAccount}/toggle-default', [BankAccountController::class, 'toggleDefault']);

        // account setup
        Route::middleware('auth:api')->post('/account/setup', AccountSetupController::class)->name('account.setup');
        // chart account categories
        Route::middleware('auth:api')->get('/me/chart-account-categories', [AccountChartCategoryController::class, 'user'])->name('user-chart-account-categories');
        Route::prefix('chart-account-categories')->name('chart-account-categories.')->group(function () {
            Route::get('/', [AccountChartCategoryController::class, 'index'])->name('chart-account-categories');
            Route::put('/{id}/toggle', [AccountChartCategoryController::class, 'toggle_category'])->name('update-chart-account-categories');
        });
        // busines info creation
        Route::post('/business-info', [BusinessInfoController::class, 'store']);
        Route::put('/business-info/{id}', [BusinessInfoController::class, 'update']);

        // Dashboard monthly analytics endpoint
        Route::get('/dashboard', [DashboardController::class, 'monthlyAnalytics']);

        // Assuming this is inside a route file that already has the api/v1 prefix
        Route::controller(ChartOfAccountController::class)->group(function () {
            Route::get('/chart-accounts', 'index');
            Route::post('/chart-accounts', 'store');
            Route::patch('/chart-accounts/{id}', 'update');
        });
    });
    Route::prefix('newsletter')->name('newsletter.')->group(function () {
        Route::get('/unsubscribe/{email}', [NewsLetterController::class, 'oneClickUnsubscribe'])->name('one-click-unsubscribe');
        Route::get('/resubscribe/{email}', [NewsLetterController::class, 'oneClickResubscribe'])->name('resubscribe');
        Route::get('/result', [NewsLetterController::class, 'showResult'])->name('result');

        Route::post('/subscribe', [NewsLetterController::class, 'subscribe'])->name('subscribe');
        Route::post('/unsubscribe', [NewsLetterController::class, 'unsubscribe'])->name('unsubscribe');
    });

    Route::prefix('plans')->group(function () {
        Route::post('/', [PlanController::class, 'store'])->name('create-plan');      // Create a plan
        Route::get('/{id}', [PlanController::class, 'show'])->name('show-plan');    // Get a plan by ID
        Route::patch('/{id}', [PlanController::class, 'update'])->name('update-plan');  // Update a plan
        Route::delete('/{id}', [PlanController::class, 'destroy'])->name('delete-plan'); // Delete a plan
    });

    // outbound marketing api
    Route::post('/outbound-marketing', [OutboundMarketingController::class, 'store']);

    // partners
    Route::post('/partners', [PartnerController::class, 'submit'])->name('partners');

    // reconciliations
    Route::middleware('auth:api')->post('/reconciliations', [ReconciliationController::class, 'store'])->middleware([CheckReconciliationLimit::class])->name('reconciliations');
    Route::middleware('auth:api')->post('/reconciliations/{reconciliation}/ledgers', [ReconciliationController::class, 'createReconWithLedgers'])->whereUuid('reconciliation')->name('reconciliation-ledgers');
    Route::middleware('auth:api')->post('/reconciliations/{reconciliation}/statements', [ReconciliationController::class, 'addStatementsToRecon'])->whereUuid('reconciliation')->name('add-statements');
    Route::middleware('auth:api')->get('/reconciliations/{reconciliation}/result', [ReconciliationController::class, 'getReconResults'])->whereUuid('reconciliation')->name('reconciled-results');
    Route::middleware('auth:api')->get('/reconciliations/{reconciliation}/summary', [ReconciliationController::class, 'getSummary'])->whereUuid('reconciliation')->name('reconciled-summary');
    Route::middleware('auth:api')->post('/reconciliations/{reconciliation}/start', [ReconciliationController::class, 'processWithEmbeddings'])->whereUuid('reconciliation')->name('start-reconciliation');
    Route::middleware('auth:api')->put('/reconciliations/{reconciliation}/complete', [ReconciliationController::class, 'complete'])->whereUuid('reconciliation')->name('reconciled-complete');
    Route::middleware('auth:api')->put('/reconciliations/{reconciliation}', [ReconciliationController::class, 'saveDraft'])->whereUuid('reconciliation')->name('draft');
    Route::middleware('auth:api')->get('/reconciliations/{reconciliation}', [ReconciliationController::class, 'getReconciledRecords'])->whereUuid('reconciliation')->name('reconciled-records');
    Route::middleware('auth:api')->get('/reconciliations', [ReconciliationController::class, 'listUserReconciliations'])->name('list');
    Route::get('/reconciliations/{reconciliation}/export', [ReconciliationController::class, 'export'])->name('export');
    Route::middleware('auth:api')->delete('/reconciliations/{reconciliation}', [ReconciliationController::class, 'destroy'])->whereUuid('reconciliation')->name('delete-reconciliation');
    Route::middleware('auth:api')->post('/reconcile', [ReconciliationController::class, 'testEmbeddings'])->middleware([CheckReconciliationLimit::class])->name('reconcile');
    Route::post('/reconcile/{reconciliation}', [ReconciliationController::class, 'matchUnmatch'])->whereUuid('reconciliation')->name('manual-reconciliation');


    Route::post('/wait-list', [WaitListController::class, 'store'])->name('wait-list');
    Route::post('/contact', [ContactController::class, 'contact'])->name('contact');

    // feebback api
    Route::post('/customer-feedback', [CustomerFeedbackController::class, 'store'])->name('customer.feedback');

    // jobs

    Route::post('/job-application', [JobApplicationController::class, 'store']);
});
