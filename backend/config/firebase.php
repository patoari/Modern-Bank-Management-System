<?php

return [
    'server_key' => env('FCM_SERVER_KEY'),
    'url' => env('FCM_URL', 'https://fcm.googleapis.com/fcm/send'),
    'project_id' => env('FCM_PROJECT_ID'),
    'sender_id' => env('FCM_SENDER_ID'),
];
