<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
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

        $beneficiaries = Beneficiary::where('customer_id', $customer->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $beneficiaries,
            'count' => $beneficiaries->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'beneficiary_name' => 'required|string|max:100',
            'account_number'   => 'required|string|max:20',
            'ifsc_code'        => 'nullable|string|max:20',
            'bank_name'        => 'nullable|string|max:100',
            'account_type'     => 'required|in:savings,current',
            'relationship'     => 'required|in:self,family,business,other',
        ]);

        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'No customer profile found.'], 403);
        }

        // Check if beneficiary already exists
        $existing = Beneficiary::where('customer_id', $customer->id)
            ->where('account_number', $data['account_number'])
            ->where('is_active', true)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This account is already added as a beneficiary.',
            ], 422);
        }

        $beneficiary = Beneficiary::create(array_merge(
            $data,
            ['customer_id' => $customer->id]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary added successfully.',
            'data' => $beneficiary,
        ], 201);
    }

    public function show(Request $request, Beneficiary $beneficiary): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer || $beneficiary->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $beneficiary,
        ]);
    }

    public function update(Request $request, Beneficiary $beneficiary): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer || $beneficiary->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate([
            'beneficiary_name' => 'sometimes|string|max:100',
            'bank_name'        => 'sometimes|nullable|string|max:100',
            'relationship'     => 'sometimes|in:self,family,business,other',
        ]);

        $beneficiary->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary updated successfully.',
            'data' => $beneficiary,
        ]);
    }

    public function destroy(Request $request, Beneficiary $beneficiary): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer || $beneficiary->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $beneficiary->update(['is_active' => false]);
        $beneficiary->delete();

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary removed successfully.',
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_number' => 'required|string|max:20',
            'ifsc_code'      => 'nullable|string|max:20',
        ]);

        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'No customer profile found.'], 403);
        }

        // Verify account exists in system
        $account = Account::where('account_number', $data['account_number'])
            ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account does not exist in the system.',
            ], 422);
        }

        if ($account->account_status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not in active status.',
            ], 422);
        }

        // Check if it's own account
        if ($account->customer_id === $customer->id) {
            $verification_type = 'self';
        } else {
            // Third-party account - would require actual verification process
            $verification_type = 'third-party';
        }

        return response()->json([
            'success' => true,
            'message' => 'Account verification successful.',
            'data' => [
                'account_number' => $account->account_number,
                'account_holder' => $account->account_title,
                'account_type' => $account->accountType->name ?? 'Unknown',
                'verification_type' => $verification_type,
                'account_status' => $account->account_status,
            ],
        ]);
    }
}
