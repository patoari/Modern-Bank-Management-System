<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'notification_type', 'channel', 'title', 'message',
        'is_read', 'read_at', 'reference_type', 'reference_id', 'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope for unread notifications
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // Scope for read notifications
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    // Mark as read
    public function markAsRead()
    {
        return $this->update(['read_at' => now()]);
    }
}
