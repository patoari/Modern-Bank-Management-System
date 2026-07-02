<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(private AccountService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);
        $accounts = $this->service->list($request->all(), $request->integer('per_page', 15));
        return response()->json(['success' => true, 'data' => $accounts]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Account::class);
        $data = $request->validate([
            'customer_id'     => 'required|exists:customers,id',
            'account_type_id' => 'required|exists:account_types,id',
            'branch_id'       => 'required|exists:branches,id',
            'account_title'   => 'required|string|max:255',
            'currency_code'   => 'nullable|string|size:3',
            'initial_deposit' => 'nullable|numeric|min:0',
            'interest_rate'   => 'nullable|numeric|min:0',
            'minimum_balance' => 'nullable|numeric|min:0',
        ]);

        $account = $this->service->open($data, $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Account opened.', 'data' => $account], 201);
    }

    public function show(Account $account): JsonResponse
    {
        $this->authorize('view', $account);
        $account->load(['customer.user', 'accountType', 'branch', 'jointHolders.customer', 'standingInstructions']);
        return response()->json(['success' => true, 'data' => $account]);
    }

    public function freeze(Request $request, Account $account): JsonResponse
    {
        $this->authorize('freeze', $account);
        $data    = $request->validate(['reason' => 'required|string|max:500']);
        $account = $this->service->freeze($account, $data['reason'], $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Account frozen.', 'data' => $account]);
    }

    public function unfreeze(Request $request, Account $account): JsonResponse
    {
        $this->authorize('freeze', $account);
        $account = $this->service->unfreeze($account, $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Account unfrozen.', 'data' => $account]);
    }

    public function close(Request $request, Account $account): JsonResponse
    {
        $this->authorize('close', $account);
        $data    = $request->validate(['reason' => 'required|string|max:500']);
        $account = $this->service->close($account, $data['reason'], $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Account closed.', 'data' => $account]);
    }

    public function statement(Request $request, Account $account): JsonResponse
    {
        $this->authorize('view', $account);
        $data = $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date|after_or_equal:from_date',
        ]);
        $transactions = $this->service->getStatement($account, $data['from_date'], $data['to_date']);
        return response()->json(['success' => true, 'data' => ['account' => $account, 'transactions' => $transactions]]);
    }
}
