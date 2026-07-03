<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerComplaint extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'complaint_ref', 'customer_id', 'complaint_category', 'complaint_type',
        'priority', 'description', 'status', 'resolution_notes', 'compensation_amount',
        'compensation_status', 'assigned_to', 'registered_date', 'target_resolution_date',
        'resolved_date',
    ];

    protected $casts = [
        'registered_date' => 'date',
        'target_resolution_date' => 'date',
        'resolved_date' => 'date',
        'compensation_amount' => 'decimal:12,2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
