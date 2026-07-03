<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChequeBook;
use App\Models\Cheque;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChequeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // Cheque Book Management
    public function requestChequeBook(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'quantity' => 'required|integer|in:10,20,50',
            'delivery_mode' => 'required|in:counter,courier,home_delivery',
            'delivery_address' => 'nullable|string|max:500',
        ]);

        $customer = $request->user()->customer;
        $account = Account::find($data['account_id']);

        // Verify ownership
        if ($account->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        // Verify account has cheque book feature
        if (!$account->accountType->allow_cheque_book) {
            return response()->json([
                'success' => false,
                'message' => 'This account type does not support cheque books.',
            ], 422);
        }

        $chequeBookNumber = 'CB-' . $account->account_number . '-' . now()->format('YmdHis');
        
        $chequeBook = ChequeBook::create([
            'cheque_book_number' => $chequeBookNumber,
            'account_id' => $account->id,
            'from_cheque_no' => sprintf('%010d', random_int(1000000, 9999999)),
            'to_cheque_no' => sprintf('%010d', random_int(1000000, 9999999) + $data['quantity']),
            'status' => 'requested',
            'delivery_mode' => $data['delivery_mode'],
            'delivery_address' => $data['delivery_address'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cheque book request submitted successfully.',
            'data' => $chequeBook,
        ], 201);
    }

    public function chequeBooks(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        $chequeBooks = ChequeBook::whereIn('account_id',
            Account::where('customer_id', $customer->id)->pluck('id')
        )
        ->with('account')
        ->orderBy('created_at', 'desc')
        ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $chequeBooks,
        ]);
    }

    public function cheques(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        $cheques = Cheque::whereIn('account_id',
            Account::where('customer_id', $customer->id)->pluck('id')
        )
        ->with(['account', 'chequeBook'])
        ->orderBy('created_at', 'desc')
        ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $cheques,
        ]);
    }

    public function stopCheque(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cheque_number' => 'required|string|max:20',
            'reason' => 'required|string|max:500',
        ]);

        $customer = $request->user()->customer;
        $cheque = Cheque::where('cheque_number', $data['cheque_number'])
            ->whereIn('account_id', Account::where('customer_id', $customer->id)->pluck('id'))
            ->first();

        if (!$cheque) {
            return response()->json(['success' => false, 'message' => 'Cheque not found.'], 404);
        }

        if (!in_array($cheque->status, ['available', 'issued'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot stop a cheque with this status.',
            ], 422);
        }

        $cheque->update([
            'status' => 'stopped',
            'stop_reason' => $data['reason'],
            'stopped_at' => now(),
            'stopped_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cheque stopped successfully.',
            'data' => $cheque,
        ]);
    }

    public function chequeMinus(Request $request, $chequeNumber): JsonResponse
    {
        $customer = $request->user()->customer;
        $cheque = Cheque::where('cheque_number', $chequeNumber)
            ->whereIn('account_id', Account::where('customer_id', $customer->id)->pluck('id'))
            ->first();

        if (!$cheque) {
            return response()->json(['success' => false, 'message' => 'Cheque not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cheque_number' => $cheque->cheque_number,
                'account_title' => $cheque->account->account_title,
                'ifsc' => 'DEFAULT_IFSC',
                'account_number' => $cheque->account->account_number,
                'status' => $cheque->status,
            ],
        ]);
    }
}
