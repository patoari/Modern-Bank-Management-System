<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function transactionSummary(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date|after_or_equal:from_date',
        ]);

        $data = Transaction::select(
                'transaction_type',
                'transaction_status',
                'currency_code',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as avg_amount')
            )
            ->whereBetween('value_date', [$request->from_date, $request->to_date])
            ->groupBy('transaction_type', 'transaction_status', 'currency_code')
            ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function loanPortfolio(Request $request): JsonResponse
    {
        $portfolio = Loan::select(
                'loan_status',
                'npa_classification',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(principal_amount) as total_principal'),
                DB::raw('SUM(total_outstanding) as total_outstanding'),
                DB::raw('SUM(penalty_amount) as total_penalty')
            )
            ->whereNull('deleted_at')
            ->groupBy('loan_status', 'npa_classification')
            ->get();

        $overdueLoans = Loan::with(['customer.user', 'loanProduct', 'branch'])
            ->whereNull('deleted_at')
            ->where('overdue_days', '>', 0)
            ->orderByDesc('overdue_days')
            ->limit(50)
            ->get();

        return response()->json(['success' => true, 'data' => ['portfolio' => $portfolio, 'overdue_loans' => $overdueLoans]]);
    }

    public function customerSegmentation(): JsonResponse
    {
        $data = Customer::select(
                'segment', 'kyc_status', 'risk_rating',
                DB::raw('COUNT(*) as count')
            )
            ->whereNull('deleted_at')
            ->groupBy('segment', 'kyc_status', 'risk_rating')
            ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function accountBalanceSummary(Request $request): JsonResponse
    {
        $data = Account::select(
                'currency_code', 'account_status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(available_balance) as total_available'),
                DB::raw('SUM(ledger_balance) as total_ledger'),
                DB::raw('SUM(hold_amount) as total_hold')
            )
            ->whereNull('deleted_at')
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->groupBy('currency_code', 'account_status')
            ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function branchPerformance(): JsonResponse
    {
        $data = DB::table('branches as b')
            ->leftJoin('accounts as a', fn($j) => $j->on('b.id', '=', 'a.branch_id')->where('a.account_status', 'active')->whereNull('a.deleted_at'))
            ->leftJoin('loans as l', fn($j) => $j->on('b.id', '=', 'l.branch_id')->whereIn('l.loan_status', ['active', 'disbursed'])->whereNull('l.deleted_at'))
            ->select(
                'b.id', 'b.branch_code', 'b.branch_name', 'b.branch_type',
                DB::raw('COUNT(DISTINCT a.id) as total_accounts'),
                DB::raw('SUM(a.available_balance) as total_deposits'),
                DB::raw('COUNT(DISTINCT l.id) as total_loans'),
                DB::raw('SUM(l.total_outstanding) as loan_outstanding')
            )
            ->where('b.status', 'active')
            ->groupBy('b.id', 'b.branch_code', 'b.branch_name', 'b.branch_type')
            ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }
}
