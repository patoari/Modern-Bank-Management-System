<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ============================================================
// PUBLIC ROUTES
// ============================================================
Route::prefix('v1')->group(function () {

    // Auth
    Route::post('auth/login',  [AuthController::class, 'login']);

    // EMI Calculator (public utility)
    Route::get('loans/calculate-emi', [LoanController::class, 'calculateEmi']);

    // ============================================================
    // AUTHENTICATED ROUTES
    // ============================================================
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('auth/logout',          [AuthController::class, 'logout']);
        Route::get('auth/me',               [AuthController::class, 'me']);
        Route::post('auth/refresh',         [AuthController::class, 'refresh']);
        Route::post('auth/change-password', [AuthController::class, 'changePassword']);

        // Dashboard
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);

        // Customers
        Route::apiResource('customers', CustomerController::class);
        Route::patch('customers/{customer}/kyc',      [CustomerController::class, 'updateKyc']);
        Route::get('customers/{customer}/accounts',   [CustomerController::class, 'accounts']);

        // Accounts
        Route::apiResource('accounts', AccountController::class)->only(['index', 'store', 'show']);
        Route::post('accounts/{account}/freeze',    [AccountController::class, 'freeze']);
        Route::post('accounts/{account}/unfreeze',  [AccountController::class, 'unfreeze']);
        Route::post('accounts/{account}/close',     [AccountController::class, 'close']);
        Route::get('accounts/{account}/statement',  [AccountController::class, 'statement']);

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
