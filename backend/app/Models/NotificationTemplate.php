<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'event_key',
        'channel',
        'subject',
        'body',
        'variables',
        'enabled',
    ];

    protected $casts = [
        'variables' => 'array',
        'enabled' => 'boolean',
    ];
}
