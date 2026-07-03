<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\PushToken;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationManager
{
    public function __construct(
        private EmailService $emailService,
        private SMSService $smsService,
        private PushService $pushService
    ) {
    }

    public function send(int $userId, string $eventType, array $notificationData): bool
    {
        $user = \App\Models\User::find($userId);
        if (! $user) {
            Log::warning('NotificationManager: user not found', ['user_id' => $userId]);
            return false;
        }

        $template = NotificationTemplate::where('event_key', $eventType)->first();
        $channels = $notificationData['channels'] ?? ['in_app', 'email', 'sms', 'push'];

        foreach ($channels as $channel) {
            if (! $this->isChannelEnabled($user, $channel)) {
                continue;
            }

            $messageData = $this->buildMessage($user, $eventType, $notificationData, $template, $channel);
            $this->dispatchChannel($user, $channel, $messageData, $eventType);
        }

        return true;
    }

    private function isChannelEnabled($user, string $channel): bool
    {
        return match ($channel) {
            'email' => config('notification.default_channels.email', true) && $user->customer?->communication_preference !== 'none',
            'sms' => config('notification.default_channels.sms', false) && $user->customer?->communication_preference !== 'none',
            'push' => config('notification.default_channels.push', true),
            default => true,
        };
    }

    private function buildMessage($user, string $eventType, array $data, $template, string $channel): array
    {
        $account = $data['account'] ?? null;
        $maskedAccount = $account?->account_number ? '****' . substr($account->account_number, -4) : null;

        $base = [
            'user_id' => $user->id,
            'notification_type' => $eventType,
            'channel' => $channel,
            'title' => $data['title'] ?? ucfirst(str_replace('_', ' ', $eventType)),
            'message' => $data['message'] ?? $template?->body ?? 'A notification was generated.',
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ];

        $viewData = array_merge($data, [
            'bank_name' => config('notification.bank_name'),
            'bank_logo_url' => config('notification.bank_logo_url'),
            'customer_name' => $user->customer?->full_name ?? $user->email,
            'account_number' => $maskedAccount,
            'transaction_id' => $data['transaction_id'] ?? $data['reference_id'],
            'support_email' => config('notification.support_email'),
            'support_phone' => config('notification.support_phone'),
        ]);

        return array_merge($base, [
            'template' => $template,
            'view_data' => $viewData,
            'subject' => $data['subject'] ?? $template?->subject ?? $base['title'],
            'html_body' => $data['html_body'] ?? null,
        ]);
    }

    private function dispatchChannel($user, string $channel, array $messageData, string $eventType): void
    {
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'notification_type' => $eventType,
            'channel' => $channel,
            'provider' => config("notification.{$channel}_driver"),
            'title' => $messageData['title'],
            'message' => $messageData['message'],
            'status' => 'pending',
            'payload' => $messageData,
        ]);

        $status = false;
        $response = null;

        if ($channel === 'email' && !empty($user->email)) {
            $status = $this->emailService->send(array_merge($messageData, [
                'to_email' => $user->email,
                'to_name' => $user->customer?->full_name,
                'from_email' => config('brevo.sender_email'),
                'from_name' => config('brevo.sender_name'),
            ]));
        }

        if ($channel === 'sms' && !empty($user->phone)) {
            $status = $this->smsService->send([
                'phone' => $user->phone,
                'message' => $messageData['message'],
            ]);
        }

        if ($channel === 'push') {
            $tokens = PushToken::where('user_id', $user->id)->where('enabled', true)->pluck('device_token')->all();
            if (!empty($tokens)) {
                $status = $this->pushService->send([
                    'tokens' => $tokens,
                    'title' => $messageData['title'],
                    'message' => $messageData['message'],
                    'icon' => $messageData['metadata']['icon'] ?? null,
                    'click_action' => $messageData['metadata']['click_action'] ?? null,
                    'data' => $messageData['metadata'] ?? [],
                ]);
            }
        }

        $log->update([
            'status' => $status ? 'sent' : 'failed',
            'response' => $response ?? ($status ? 'ok' : 'failed'),
        ]);

        if ($channel === 'in_app') {
            Notification::create([
                'user_id' => $user->id,
                'notification_type' => $eventType,
                'channel' => 'in_app',
                'title' => $messageData['title'],
                'message' => $messageData['message'],
                'reference_type' => $messageData['reference_type'],
                'reference_id' => $messageData['reference_id'],
                'metadata' => $messageData['metadata'],
            ]);
        }
    }
}
