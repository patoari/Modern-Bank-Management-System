<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id','type','channel','title','message',
        'is_read','read_at','reference_type','reference_id','metadata',
    ];
    protected $casts = [
        'is_read'=>'boolean','read_at'=>'datetime','metadata'=>'array',
    ];
    public function user() { return $this->belongsTo(User::class); }
}
