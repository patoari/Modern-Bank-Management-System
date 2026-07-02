<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Transaction::class);
        $transactions = $this->service->list($request->all(), $request->integer('per_page', 15));
        return response()->json(['success' => true, 'data' => $transactions]);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);
        $transaction->load(['fromAccount.customer', 'toAccount.customer', 'initiatedBy', 'approvedBy', 'branch', 'approvals.approver']);
        return response()->json(['success' => true, 'data' => $transaction]);
    }

    public function deposit(Request $request): JsonResponse
    {
        $this->authorize('deposit', Transaction::class);
        $data = $request->validate([
            'account_id'  => 'required|exists:accounts,id',
            'amount'      => 'required|numeric|min:0.01',
            'mode'        => 'nullable|in:cash,cheque,neft,rtgs,imps,upi',
            'description' => 'nullable|string|max:500',
            'branch_id'   => 'nullable|exists:branches,id',
        ]);
        $txn = $this->service->deposit($data, $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Deposit successful.', 'data' => $txn], 201);
    }

    public function withdraw(Request $request): JsonResponse
    {
        $this->authorize('withdraw', Transaction::class);
        $data = $request->validate([
            'account_id'  => 'required|exists:accounts,id',
            'amount'      => 'required|numeric|min:0.01',
            'mode'        => 'nullable|in:cash,cheque,atm',
            'description' => 'nullable|string|max:500',
            'branch_id'   => 'nullable|exists:branches,id',
        ]);
        $txn = $this->service->withdraw($data, $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Withdrawal processed.', 'data' => $txn], 201);
    }

    public function transfer(Request $request): JsonResponse
    {
        $this->authorize('transfer', Transaction::class);
        $data = $request->validate([
            'from_account_id'  => 'required|exists:accounts,id',
            'to_account_number'=> 'required|string|exists:accounts,account_number',
            'amount'           => 'required|numeric|min:0.01',
            'mode'             => 'nullable|in:internal,neft,rtgs,imps,upi',
            'description'      => 'nullable|string|max:500',
        ]);
        $txn = $this->service->transfer($data, $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Transfer processed.', 'data' => $txn], 201);
    }

    public function approve(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('approve', $transaction);
        $txn = $this->service->approve($transaction, $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Transaction approved.', 'data' => $txn]);
    }

    public function reverse(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('reverse', $transaction);
        $data = $request->validate(['reason' => 'required|string|max:500']);
        $txn  = $this->service->reverse($transaction, $data['reason'], $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Transaction reversed.', 'data' => $txn]);
    }
}
