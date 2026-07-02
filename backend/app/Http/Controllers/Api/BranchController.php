<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $branches = Branch::with(['manager.user'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('branch_name', 'like', "%{$request->search}%")
                                                   ->orWhere('branch_code', 'like', "%{$request->search}%"))
            ->latest()->paginate($request->integer('per_page', 15));
        return response()->json(['success' => true, 'data' => $branches]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Branch::class);
        $data = $request->validate([
            'branch_code'  => 'required|string|max:20|unique:branches',
            'branch_name'  => 'required|string|max:255',
            'branch_type'  => 'required|in:main,regional,urban,rural,atm',
            'ifsc_code'    => 'required|string|max:20|unique:branches',
            'swift_code'   => 'nullable|string|max:20',
            'email'        => 'required|email',
            'phone'        => 'required|string|max:20',
            'address_line1'=> 'required|string|max:255',
            'city'         => 'required|string|max:100',
            'state'        => 'required|string|max:100',
            'country'      => 'required|string|max:100',
            'postal_code'  => 'required|string|max:20',
            'opened_date'  => 'required|date',
        ]);

        $branch = Branch::create(array_merge($data, ['status' => 'active']));
        return response()->json(['success' => true, 'message' => 'Branch created.', 'data' => $branch], 201);
    }

    public function show(Branch $branch): JsonResponse
    {
        $branch->load(['staff.user', 'manager.user', 'atms']);
        return response()->json(['success' => true, 'data' => $branch]);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('update', $branch);
        $data = $request->validate([
            'branch_name'  => 'sometimes|string|max:255',
            'email'        => 'sometimes|email',
            'phone'        => 'sometimes|string|max:20',
            'address_line1'=> 'sometimes|string|max:255',
            'status'       => 'sometimes|in:active,inactive,under_renovation',
            'manager_id'   => 'sometimes|nullable|exists:staff,id',
        ]);
        $branch->update($data);
        return response()->json(['success' => true, 'message' => 'Branch updated.', 'data' => $branch]);
    }
}
