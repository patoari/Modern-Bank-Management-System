<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AmlAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function logs(Request $request): JsonResponse
    {
        $this->authorize('viewLogs', AuditLog::class);
        $logs = AuditLog::with('user')
            ->when($request->module,     fn($q) => $q->where('module', $request->module))
            ->when($request->action,     fn($q) => $q->where('action', $request->action))
            ->when($request->user_id,    fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->from_date,  fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date,    fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->latest('created_at')->paginate($request->integer('per_page', 25));
        return response()->json(['success' => true, 'data' => $logs]);
    }

    public function amlAlerts(Request $request): JsonResponse
    {
        $this->authorize('viewAml', AmlAlert::class);
        $alerts = AmlAlert::with(['customer.user', 'transaction', 'assignedTo'])
            ->when($request->severity,     fn($q) => $q->where('severity', $request->severity))
            ->when($request->alert_status, fn($q) => $q->where('alert_status', $request->alert_status))
            ->when($request->customer_id,  fn($q) => $q->where('customer_id', $request->customer_id))
            ->latest()->paginate($request->integer('per_page', 15));
        return response()->json(['success' => true, 'data' => $alerts]);
    }

    public function reviewAmlAlert(Request $request, AmlAlert $alert): JsonResponse
    {
        $this->authorize('reviewAml', $alert);
        $data = $request->validate([
            'alert_status'     => 'required|in:under_review,closed,escalated,false_positive',
            'resolution_notes' => 'nullable|string|max:2000',
            'str_filed'        => 'nullable|boolean',
            'str_reference'    => 'nullable|string|max:100',
        ]);

        $alert->update(array_merge($data, [
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]));

        return response()->json(['success' => true, 'message' => 'AML alert updated.', 'data' => $alert]);
    }
}
