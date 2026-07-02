<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(private LoanService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Loan::class);
        $loans = $this->service->list($request->all(), $request->integer('per_page', 15));
        return response()->json(['success' => true, 'data' => $loans]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Loan::class);
        $data = $request->validate([
            'customer_id'      => 'required|exists:customers,id',
            'loan_product_id'  => 'required|exists:loan_products,id',
            'branch_id'        => 'required|exists:branches,id',
            'principal_amount' => 'required|numeric|min:1000',
            'interest_rate'    => 'required|numeric|min:0.01|max:100',
            'tenure_months'    => 'required|integer|min:1|max:360',
            'interest_type'    => 'nullable|in:fixed,floating,reducing_balance',
            'purpose'          => 'nullable|string|max:500',
            'collateral_type'  => 'nullable|string|max:100',
            'collateral_value' => 'nullable|numeric|min:0',
        ]);
        $loan = $this->service->apply($data, $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Loan application submitted.', 'data' => $loan], 201);
    }

    public function show(Loan $loan): JsonResponse
    {
        $this->authorize('view', $loan);
        $loan->load(['customer.user', 'loanProduct', 'branch', 'repaymentSchedule', 'documents', 'loanOfficer.user']);
        return response()->json(['success' => true, 'data' => $loan]);
    }

    public function approve(Request $request, Loan $loan): JsonResponse
    {
        $this->authorize('approve', $loan);
        $data = $request->validate([
            'sanctioned_amount' => 'nullable|numeric|min:0',
            'interest_rate'     => 'nullable|numeric|min:0.01|max:100',
        ]);
        $loan = $this->service->approve($loan, $data, $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Loan approved.', 'data' => $loan]);
    }

    public function reject(Request $request, Loan $loan): JsonResponse
    {
        $this->authorize('approve', $loan);
        $data = $request->validate(['reason' => 'required|string|max:1000']);
        $loan = $this->service->reject($loan, $data['reason'], $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Loan rejected.', 'data' => $loan]);
    }

    public function disburse(Request $request, Loan $loan): JsonResponse
    {
        $this->authorize('disburse', $loan);
        $loan = $this->service->disburse($loan, $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Loan disbursed.', 'data' => $loan]);
    }

    public function repaymentSchedule(Loan $loan): JsonResponse
    {
        $this->authorize('view', $loan);
        return response()->json(['success' => true, 'data' => $loan->repaymentSchedule()->orderBy('installment_number')->get()]);
    }

    public function calculateEmi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'principal'     => 'required|numeric|min:1',
            'interest_rate' => 'required|numeric|min:0.01',
            'tenure_months' => 'required|integer|min:1',
        ]);
        $emi = $this->service->calculateEmi($data['principal'], $data['interest_rate'], $data['tenure_months']);
        return response()->json(['success' => true, 'data' => ['emi' => $emi]]);
    }
}
