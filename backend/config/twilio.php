<?php

return [
    'sid' => env('TWILIO_SID'),
    'token' => env('TWILIO_TOKEN'),
    'from' => env('TWILIO_FROM'),
    'url' => env('TWILIO_API_URL', 'https://api.twilio.com/2010-04-01/Accounts'),
];
