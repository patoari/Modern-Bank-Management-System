<?php

namespace App\Services\Notifications;

use Illuminate\Mail\Mailer;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Throwable;

class EmailService
{
    public function __construct(private Mailer $mailer, private Markdown $markdown)
    {
    }

    public function send(array $data): bool
    {
        $driver = config('notification.email_driver', 'smtp');
        $template = $data['template'] ?? 'emails.notification';

        try {
            if ($driver === 'api') {
                return $this->sendViaApi($data);
            }

            return $this->sendViaSmtp($data, $template);
        } catch (Throwable $exception) {
            Log::error('EmailService failed', ['error' => $exception->getMessage(), 'data' => $data]);
            return false;
        }
    }

    private function sendViaSmtp(array $data, string $template): bool
    {
        $this->mailer->send($template, $data['view_data'] ?? [], function ($message) use ($data) {
            $message->to($data['to_email'], $data['to_name'] ?? null)
                ->subject($data['subject'] ?? 'Notification from ' . config('notification.bank_name'));

            if (!empty($data['from_email'])) {
                $message->from($data['from_email'], $data['from_name'] ?? config('notification.bank_name'));
            }
        });

        return count($this->mailer->failures()) === 0;
    }

    private function sendViaApi(array $data): bool
    {
        $payload = [
            'sender' => ['email' => config('brevo.sender_email'), 'name' => config('brevo.sender_name')],
            'to' => [[
                'email' => $data['to_email'],
                'name' => $data['to_name'] ?? null,
            ]],
            'subject' => $data['subject'] ?? 'Notification from ' . config('notification.bank_name'),
            'htmlContent' => $data['html_body'] ?? $this->renderHtmlContent($data),
        ];

        $response = app('http')->withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'api-key' => config('brevo.api_key'),
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        return $response->successful();
    }

    private function renderHtmlContent(array $data): string
    {
        return View::make($data['html_template'] ?? 'emails.notification', $data['view_data'] ?? [])->render();
    }
}
