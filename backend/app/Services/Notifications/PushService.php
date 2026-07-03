<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushService
{
    public function send(array $data): bool
    {
        try {
            $payload = [
                'registration_ids' => $data['tokens'],
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['message'],
                    'icon' => $data['icon'] ?? null,
                    'click_action' => $data['click_action'] ?? null,
                ],
                'data' => $data['data'] ?? [],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . config('firebase.server_key'),
                'Content-Type' => 'application/json',
            ])->post(config('firebase.url'), $payload);

            return $response->successful();
        } catch (Throwable $exception) {
            Log::error('PushService failed', ['error' => $exception->getMessage(), 'data' => $data]);
            return false;
        }
    }
}
