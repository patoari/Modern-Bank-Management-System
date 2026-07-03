<?php

return [
    'driver' => env('BREVO_DRIVER', 'api'),
    'api_key' => env('BREVO_API_KEY'),
    'sender_email' => env('BREVO_SENDER_EMAIL', env('MAIL_FROM_ADDRESS', 'no-reply@example.com')),
    'sender_name' => env('BREVO_SENDER_NAME', env('MAIL_FROM_NAME', 'Bank Management System')),
    'smtp' => [
        'host' => env('BREVO_SMTP_HOST', 'smtp-relay.brevo.com'),
        'port' => env('BREVO_SMTP_PORT', 587),
        'username' => env('BREVO_SMTP_USERNAME'),
        'password' => env('BREVO_SMTP_PASSWORD'),
        'encryption' => env('BREVO_SMTP_ENCRYPTION', 'tls'),
    ],
];
