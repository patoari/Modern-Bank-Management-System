<?php

return [
    'email_driver' => env('NOTIFICATION_EMAIL_DRIVER', 'smtp'),
    'sms_driver' => env('NOTIFICATION_SMS_DRIVER', 'textbelt'),
    'push_driver' => env('NOTIFICATION_PUSH_DRIVER', 'firebase'),

    'default_channels' => [
        'email' => true,
        'sms' => false,
        'push' => true,
    ],

    'support_email' => env('NOTIFICATION_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', 'support@example.com')),
    'support_phone' => env('NOTIFICATION_SUPPORT_PHONE', '+880123456789'),
    'bank_name' => env('APP_NAME', 'Bank Management System'),
    'bank_logo_url' => env('NOTIFICATION_BANK_LOGO_URL', null),
];
