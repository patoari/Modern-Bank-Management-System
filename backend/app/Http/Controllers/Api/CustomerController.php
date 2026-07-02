<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);
        $customers = $this->service->list($request->all(), $request->integer('per_page', 15));
        return response()->json(['success' => true, 'data' => $customers]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Customer::class);
        $data = $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'nullable|string|max:20|unique:users,phone',
            'date_of_birth'  => 'nullable|date|before:today',
            'gender'         => 'nullable|in:male,female,other',
            'nationality'    => 'nullable|string|max:100',
            'occupation'     => 'nullable|string|max:100',
            'customer_type'  => 'nullable|in:individual,business,corporate',
            'segment'        => 'nullable|in:retail,sme,corporate,hni,premier',
            'annual_income'  => 'nullable|numeric|min:0',
            'source_of_funds'=> 'nullable|string|max:255',
            'address'        => 'nullable|array',
            'address.address_type'  => 'nullable|string|max:50',
            'address.address_line1' => 'nullable|string|max:255',
            'address.city'          => 'nullable|string|max:100',
            'address.state'         => 'nullable|string|max:100',
            'address.country'       => 'nullable|string|max:100',
            'address.postal_code'   => 'nullable|string|max:20',
        ]);

        $customer = $this->service->create($data, $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Customer created.', 'data' => $customer], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);
        $customer->load(['user', 'addresses', 'kycDocuments', 'accounts.accountType', 'loans.loanProduct', 'cards.cardProduct', 'fixedDeposits', 'amlAlerts']);
        return response()->json(['success' => true, 'data' => $customer]);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);
        $data = $request->validate([
            'first_name'      => 'sometimes|string|max:100',
            'last_name'       => 'sometimes|string|max:100',
            'phone'           => 'sometimes|string|max:20',
            'date_of_birth'   => 'sometimes|date',
            'gender'          => 'sometimes|in:male,female,other',
            'nationality'     => 'sometimes|string|max:100',
            'occupation'      => 'sometimes|string|max:100',
            'annual_income'   => 'sometimes|numeric|min:0',
            'source_of_funds' => 'sometimes|string|max:255',
            'risk_rating'     => 'sometimes|in:low,medium,high,very_high',
            'segment'         => 'sometimes|in:retail,sme,corporate,hni,premier',
        ]);

        $customer = $this->service->update($customer, $data, $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Customer updated.', 'data' => $customer]);
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);
        $customer->delete();
        return response()->json(['success' => true, 'message' => 'Customer deleted.']);
    }

    public function updateKyc(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('kyc', $customer);
        $data = $request->validate(['kyc_status' => 'required|in:pending,under_review,approved,rejected']);
        $customer = $this->service->updateKyc($customer, $data['kyc_status'], $request->user()->id);
        return response()->json(['success' => true, 'message' => 'KYC status updated.', 'data' => $customer]);
    }

    public function accounts(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);
        $accounts = $customer->accounts()->with(['accountType', 'branch'])->whereNull('deleted_at')->get();
        return response()->json(['success' => true, 'data' => $accounts]);
    }
}
