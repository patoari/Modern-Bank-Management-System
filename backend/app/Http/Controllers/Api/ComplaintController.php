<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerComplaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
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

        $query = CustomerComplaint::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $complaints = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $complaints,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'complaint_category' => 'required|string|max:100',
            'complaint_type' => 'required|in:service_failure,incorrect_transaction,lost_card,documentation,other',
            'description' => 'required|string',
            'priority' => 'sometimes|in:low,medium,high,critical',
        ]);

        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'No customer profile found.'], 403);
        }

        $complaintRef = 'CMP-' . date('YmdHi') . '-' . str_pad($customer->id, 6, '0', STR_PAD_LEFT);

        $complaint = CustomerComplaint::create(array_merge(
            $data,
            [
                'customer_id' => $customer->id,
                'complaint_ref' => $complaintRef,
                'status' => 'open',
                'priority' => $data['priority'] ?? 'medium',
                'registered_date' => now()->toDateString(),
                'target_resolution_date' => now()->addDays(7)->toDateString(),
            ]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Complaint registered successfully. Reference: ' . $complaintRef,
            'data' => $complaint,
        ], 201);
    }

    public function show(Request $request, CustomerComplaint $complaint): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer || $complaint->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $complaint,
        ]);
    }

    public function update(Request $request, CustomerComplaint $complaint): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer || $complaint->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        // Customers can only add remarks, not update other fields
        $data = $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        // Append to resolution_notes
        $complaint->update([
            'resolution_notes' => ($complaint->resolution_notes ? $complaint->resolution_notes . "\n" : '') 
                . "[Customer: " . now()->format('Y-m-d H:i') . "] " . $data['remarks'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Remark added successfully.',
            'data' => $complaint,
        ]);
    }

    public function trackComplaint(Request $request, $complaintRef): JsonResponse
    {
        $customer = $request->user()->customer;

        $complaint = CustomerComplaint::where('complaint_ref', $complaintRef)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$complaint) {
            return response()->json(['success' => false, 'message' => 'Complaint not found.'], 404);
        }

        $escalationDays = now()->diffInDays($complaint->target_resolution_date);
        $isEscalated = $escalationDays < 0;

        return response()->json([
            'success' => true,
            'data' => array_merge($complaint->toArray(), [
                'days_elapsed' => now()->diffInDays($complaint->registered_date),
                'target_days' => 7,
                'is_escalated' => $isEscalated,
                'status_label' => ucfirst(str_replace('_', ' ', $complaint->status)),
            ]),
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'No customer profile found.'], 403);
        }

        $complaints = CustomerComplaint::where('customer_id', $customer->id);

        $stats = [
            'total_complaints' => $complaints->count(),
            'open_complaints' => $complaints->where('status', 'open')->count(),
            'resolved_complaints' => $complaints->where('status', 'resolved')->count(),
            'closed_complaints' => $complaints->where('status', 'closed')->count(),
            'average_resolution_days' => round(
                $complaints->whereNotNull('resolved_date')
                    ->selectRaw('DATEDIFF(resolved_date, registered_date) as days')
                    ->avg('days') ?? 0
            ),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
