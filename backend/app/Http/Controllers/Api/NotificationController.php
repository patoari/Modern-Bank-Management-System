<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('read')) {
            $read = $request->boolean('read');
            $query->where('read_at', $read ? '!=' : '=', null);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    public function show(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'data' => $notification,
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data' => $notification,
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function delete(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.',
        ]);
    }

    public function deleteAll(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All notifications deleted successfully.',
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count],
        ]);
    }

    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = [
            'transaction_alerts' => true,
            'account_alerts' => true,
            'loan_alerts' => true,
            'card_alerts' => true,
            'security_alerts' => true,
            'promotional' => false,
            'email_notifications' => true,
            'sms_notifications' => false,
            'push_notifications' => true,
        ];

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $preferences = $request->validate([
            'transaction_alerts' => 'sometimes|boolean',
            'account_alerts' => 'sometimes|boolean',
            'loan_alerts' => 'sometimes|boolean',
            'card_alerts' => 'sometimes|boolean',
            'security_alerts' => 'sometimes|boolean',
            'promotional' => 'sometimes|boolean',
            'email_notifications' => 'sometimes|boolean',
            'sms_notifications' => 'sometimes|boolean',
            'push_notifications' => 'sometimes|boolean',
        ]);

        // TODO: Save preferences to user metadata or separate table
        // For now, return updated preferences
        $request->user()->update([
            'communication_preference' => $preferences['email_notifications'] ? 'email' : 'none',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully.',
            'data' => $preferences,
        ]);
    }
}
