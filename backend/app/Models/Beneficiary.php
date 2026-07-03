<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id', 'beneficiary_name', 'account_number', 'ifsc_code',
        'bank_name', 'account_type', 'relationship', 'verification_status',
        'verification_reference', 'verified_at', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
