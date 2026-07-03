<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SMSService
{
    public function send(array $data): bool
    {
        $driver = config('notification.sms_driver', 'textbelt');

        try {
            return match ($driver) {
                'twilio' => $this->sendViaTwilio($data),
                default => $this->sendViaTextbelt($data),
            };
        } catch (Throwable $exception) {
            Log::error('SMSService failed', ['error' => $exception->getMessage(), 'data' => $data]);
            return false;
        }
    }

    private function sendViaTextbelt(array $data): bool
    {
        $response = Http::asForm()->post(config('textbelt.url'), [
            'phone' => $data['phone'],
            'message' => $data['message'],
            'key' => config('textbelt.api_key'),
        ]);

        return $response->successful() && data_get($response->json(), 'success', false) === true;
    }

    private function sendViaTwilio(array $data): bool
    {
        $sid = config('twilio.sid');
        $token = config('twilio.token');
        $from = config('twilio.from');

        $url = config('twilio.url') . "/{$sid}/Messages.json";

        $response = Http::withBasicAuth($sid, $token)->asForm()->post($url, [
            'From' => $from,
            'To' => $data['phone'],
            'Body' => $data['message'],
        ]);

        return $response->successful();
    }
}
