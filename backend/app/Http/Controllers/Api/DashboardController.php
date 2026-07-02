<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AmlAlert;
use App\Models\Customer;
use App\Models\FixedDeposit;
use App\Models\Loan;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = [
            'customers' => [
                'total'       => Customer::whereNull('deleted_at')->count(),
                'active'      => Customer::whereNull('deleted_at')->whereHas('user', fn($q) => $q->where('status', 'active'))->count(),
                'kyc_pending' => Customer::whereNull('deleted_at')->where('kyc_status', 'pending')->count(),
                'new_today'   => Customer::whereNull('deleted_at')->whereDate('created_at', today())->count(),
            ],
            'accounts' => [
                'total'          => Account::whereNull('deleted_at')->count(),
                'active'         => Account::whereNull('deleted_at')->where('account_status', 'active')->count(),
                'total_balance'  => Account::whereNull('deleted_at')->where('account_status', 'active')->sum('available_balance'),
                'frozen'         => Account::whereNull('deleted_at')->where('account_status', 'frozen')->count(),
            ],
            'transactions' => [
                'today_count'  => Transaction::whereDate('created_at', today())->count(),
                'today_volume' => Transaction::whereDate('created_at', today())->where('transaction_status', 'completed')->sum('amount'),
                'pending_approval' => Transaction::where('transaction_status', 'pending_approval')->count(),
                'monthly_volume'   => Transaction::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('transaction_status', 'completed')->sum('amount'),
            ],
            'loans' => [
                'total'           => Loan::whereNull('deleted_at')->count(),
                'active'          => Loan::whereNull('deleted_at')->whereIn('loan_status', ['active', 'disbursed'])->count(),
                'pending_approval'=> Loan::whereNull('deleted_at')->where('loan_status', 'applied')->count(),
                'total_outstanding'=> Loan::whereNull('deleted_at')->whereIn('loan_status', ['active', 'disbursed'])->sum('total_outstanding'),
                'npa_count'       => Loan::whereNull('deleted_at')->where('npa_classification', '!=', 'standard')->whereNotNull('npa_classification')->count(),
            ],
            'deposits' => [
                'total'          => FixedDeposit::count(),
                'active'         => FixedDeposit::where('fd_status', 'active')->count(),
                'total_amount'   => FixedDeposit::where('fd_status', 'active')->sum('principal_amount'),
                'maturing_soon'  => FixedDeposit::where('fd_status', 'active')->whereBetween('maturity_date', [today(), today()->addDays(30)])->count(),
            ],
            'compliance' => [
                'aml_open_alerts'    => AmlAlert::where('alert_status', 'open')->count(),
                'aml_critical'       => AmlAlert::where('alert_status', 'open')->where('severity', 'critical')->count(),
                'str_filed_month'    => AmlAlert::where('str_filed', true)->whereMonth('created_at', now()->month)->count(),
            ],
        ];

        // Recent transactions
        $recentTransactions = Transaction::with(['fromAccount.customer', 'toAccount.customer'])
            ->latest()->limit(10)->get();

        // Transaction chart data — last 7 days
        $txnChart = Transaction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN transaction_status = "completed" THEN amount ELSE 0 END) as volume'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'               => $stats,
                'recent_transactions' => $recentTransactions,
                'transaction_chart'   => $txnChart,
            ],
        ]);
    }
}
