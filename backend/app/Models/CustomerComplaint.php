<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerComplaint extends Model
{
    protected $fillable = [
        'complaint_number','customer_id','category','subject','description',
        'priority','status','assigned_to','resolved_at','resolution_notes',
    ];
    protected $casts = ['resolved_at'=>'datetime'];
    public function customer()   { return $this->belongsTo(Customer::class); }
    public function assignedTo() { return $this->belongsTo(User::class,'assigned_to'); }
}
