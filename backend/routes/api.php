<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AtmController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeneficiaryController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\ChequeController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StandingInstructionController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ============================================================
// PUBLIC ROUTES
// ============================================================
Route::prefix('v1')->group(function () {

    // Auth
    Route::post('auth/login',              [AuthController::class, 'login']);
    Route::post('auth/forgot-password',    [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password',     [AuthController::class, 'resetPassword']);

    // EMI Calculator (public utility)
    Route::get('loans/calculate-emi', [LoanController::class, 'calculateEmi']);

    // ATM Locator (public)
    Route::post('atms/nearby',           [AtmController::class, 'locateNearby']);
    Route::get('atms/city/{city}',       [AtmController::class, 'listByCity']);
    Route::post('atms/postal-code',      [AtmController::class, 'searchByPostalCode']);
    Route::get('atms/{atmId}',           [AtmController::class, 'detail']);

    // ============================================================
    // AUTHENTICATED ROUTES
    // ============================================================
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('auth/logout',          [AuthController::class, 'logout']);
        Route::get('auth/me',               [AuthController::class, 'me']);
        Route::post('auth/refresh',         [AuthController::class, 'refresh']);
        Route::post('auth/change-password', [AuthController::class, 'changePassword']);
        Route::post('auth/2fa/enable',      [AuthController::class, 'enableTwoFactor']);
        Route::post('auth/2fa/disable',     [AuthController::class, 'disableTwoFactor']);
        Route::post('auth/2fa/verify',      [AuthController::class, 'verifyOtp']);

        // Dashboard
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);

        // Customers
        Route::apiResource('customers', CustomerController::class);
        Route::patch('customers/{customer}/kyc',      [CustomerController::class, 'updateKyc']);
        Route::get('customers/{customer}/accounts',   [CustomerController::class, 'accounts']);

        // Beneficiaries
        Route::apiResource('beneficiaries', BeneficiaryController::class);
        Route::post('beneficiaries/verify', [BeneficiaryController::class, 'verify']);

        // Standing Instructions
        Route::apiResource('standing-instructions', StandingInstructionController::class);
        Route::post('standing-instructions/{standing_instruction}/pause',    [StandingInstructionController::class, 'pause']);
        Route::post('standing-instructions/{standing_instruction}/resume',   [StandingInstructionController::class, 'resume']);
        Route::get('standing-instructions/{standing_instruction}/history',   [StandingInstructionController::class, 'executionHistory']);

        // Notifications
        Route::apiResource('notifications', NotificationController::class)->only(['index', 'show', 'destroy']);
        Route::post('notifications/mark-all-read',      [NotificationController::class, 'markAllAsRead']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::get('notifications/unread-count',        [NotificationController::class, 'unreadCount']);
        Route::get('notifications/preferences',         [NotificationController::class, 'preferences']);
        Route::post('notifications/preferences',        [NotificationController::class, 'updatePreferences']);

        // Cheques
        Route::post('cheque-books/request',      [ChequeController::class, 'requestChequeBook']);
        Route::get('cheque-books',               [ChequeController::class, 'chequeBooks']);
        Route::get('cheques',                    [ChequeController::class, 'cheques']);
        Route::post('cheques/stop',              [ChequeController::class, 'stopCheque']);
        Route::get('cheques/{chequeNumber}',     [ChequeController::class, 'chequeMinus']);

        // Complaints
        Route::apiResource('complaints', ComplaintController::class)->only(['index', 'store', 'show', 'update']);
        Route::get('complaints/{complaintRef}/track',       [ComplaintController::class, 'trackComplaint']);
        Route::get('complaints/statistics',                 [ComplaintController::class, 'statistics']);

        // Accounts
        Route::apiResource('accounts', AccountController::class)->only(['index', 'store', 'show']);
        Route::post('accounts/{account}/freeze',    [AccountController::class, 'freeze']);
        Route::post('accounts/{account}/unfreeze',  [AccountController::class, 'unfreeze']);
        Route::post('accounts/{account}/close',     [AccountController::class, 'close']);
        Route::get('accounts/{account}/statement',  [AccountController::class, 'statement']);
        Route::post('accounts/{account}/export-statement', [AccountController::class, 'exportStatement']);

        // Transactions
        Route::get('transactions',                         [TransactionController::class, 'index']);
        Route::get('transactions/{transaction}',           [TransactionController::class, 'show']);
        Route::post('transactions/deposit',                [TransactionController::class, 'deposit']);
        Route::post('transactions/withdraw',               [TransactionController::class, 'withdraw']);
        Route::post('transactions/transfer',               [TransactionController::class, 'transfer']);
        Route::post('transactions/{transaction}/approve',  [TransactionController::class, 'approve']);
        Route::post('transactions/{transaction}/reverse',  [TransactionController::class, 'reverse']);

        // Loans
        Route::apiResource('loans', LoanController::class)->only(['index', 'store', 'show']);
        Route::post('loans/{loan}/approve',           [LoanController::class, 'approve']);
        Route::post('loans/{loan}/reject',            [LoanController::class, 'reject']);
        Route::post('loans/{loan}/disburse',          [LoanController::class, 'disburse']);
        Route::get('loans/{loan}/repayment-schedule', [LoanController::class, 'repaymentSchedule']);

        // Cards
        Route::apiResource('cards', CardController::class)->only(['index', 'store', 'show']);
        Route::post('cards/{card}/activate',      [CardController::class, 'activate']);
        Route::post('cards/{card}/block',         [CardController::class, 'block']);
        Route::patch('cards/{card}/limits',       [CardController::class, 'updateLimits']);

        // Branches
        Route::apiResource('branches', BranchController::class)->only(['index', 'store', 'show', 'update']);

        // Users & Roles
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/assign-role', [UserController::class, 'assignRole']);
        Route::get('roles',                     [UserController::class, 'roles']);

        // Audit & Compliance
        Route::get('audit/logs',                        [AuditController::class, 'logs']);
        Route::get('audit/aml-alerts',                  [AuditController::class, 'amlAlerts']);
        Route::patch('audit/aml-alerts/{alert}/review', [AuditController::class, 'reviewAmlAlert']);

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('transaction-summary',   [ReportController::class, 'transactionSummary']);
            Route::get('loan-portfolio',        [ReportController::class, 'loanPortfolio']);
            Route::get('customer-segmentation', [ReportController::class, 'customerSegmentation']);
            Route::get('account-balance',       [ReportController::class, 'accountBalanceSummary']);
            Route::get('branch-performance',    [ReportController::class, 'branchPerformance']);
        });
    });
});
