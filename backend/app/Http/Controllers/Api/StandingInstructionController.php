<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StandingInstruction;
use App\Models\Account;
use App\Models\Beneficiary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StandingInstructionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'No customer profile found.'], 403);
        }

        // Get standing instructions for customer's accounts
        $instructions = StandingInstruction::whereIn('from_account_id', 
            Account::where('customer_id', $customer->id)->pluck('id')
        )
        ->with(['fromAccount', 'toAccount', 'beneficiary'])
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $instructions,
            'count' => $instructions->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id'   => 'nullable|exists:accounts,id',
            'beneficiary_id'  => 'nullable|exists:beneficiaries,id',
            'instruction_name'=> 'nullable|string|max:100',
            'amount'          => 'required|numeric|min:0.01',
            'frequency'       => 'required|in:daily,weekly,fortnightly,monthly,quarterly,annually',
            'debit_type'      => 'required|in:fixed,max,variable',
            'max_amount'      => 'nullable|numeric|min:0',
            'start_date'      => 'required|date|after_or_equal:today',
            'end_date'        => 'nullable|date|after:start_date',
            'remarks'         => 'nullable|string|max:500',
        ]);

        $customer = $request->user()->customer;
        $fromAccount = Account::find($data['from_account_id']);

        // Verify account ownership
        if ($fromAccount->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        // Verify either to_account_id or beneficiary_id is provided
        if (!$data['to_account_id'] && !$data['beneficiary_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Either recipient account or beneficiary must be specified.',
            ], 422);
        }

        // Verify sufficient available balance
        if ($fromAccount->available_balance < $data['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient available balance for this instruction.',
            ], 422);
        }

        $instruction = StandingInstruction::create(array_merge(
            $data,
            [
                'status' => 'active',
                'next_execution_at' => \Carbon\Carbon::parse($data['start_date']),
            ]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Standing instruction created successfully.',
            'data' => $instruction->load(['fromAccount', 'toAccount', 'beneficiary']),
        ], 201);
    }

    public function show(Request $request, StandingInstruction $instruction): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer || $instruction->fromAccount->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $instruction->load(['fromAccount', 'toAccount', 'beneficiary']),
        ]);
    }

    public function update(Request $request, StandingInstruction $instruction): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer || $instruction->fromAccount->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate([
            'amount'          => 'sometimes|numeric|min:0.01',
            'max_amount'      => 'sometimes|nullable|numeric|min:0',
            'end_date'        => 'sometimes|nullable|date|after:' . $instruction->start_date,
            'remarks'         => 'sometimes|nullable|string|max:500',
        ]);

        $instruction->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Standing instruction updated successfully.',
            'data' => $instruction->load(['fromAccount', 'toAccount', 'beneficiary']),
        ]);
    }

    public function destroy(Request $request, StandingInstruction $instruction): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer || $instruction->fromAccount->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $instruction->update(['status' => 'cancelled']);
        $instruction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Standing instruction cancelled successfully.',
        ]);
    }

    public function pause(Request $request, StandingInstruction $instruction): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer || $instruction->fromAccount->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $instruction->update(['status' => 'suspended']);

        return response()->json([
            'success' => true,
            'message' => 'Standing instruction paused successfully.',
            'data' => $instruction,
        ]);
    }

    public function resume(Request $request, StandingInstruction $instruction): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer || $instruction->fromAccount->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $instruction->update([
            'status' => 'active',
            'next_execution_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Standing instruction resumed successfully.',
            'data' => $instruction,
        ]);
    }

    public function executionHistory(Request $request, StandingInstruction $instruction): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer || $instruction->fromAccount->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        // Get transaction records for this standing instruction
        $transactions = \App\Models\Transaction::where('standing_instruction_id', $instruction->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }
}
