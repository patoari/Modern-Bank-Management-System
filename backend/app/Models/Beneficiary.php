<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    protected $fillable = [
        'customer_id','beneficiary_name','account_number','ifsc_code',
        'bank_name','branch_name','beneficiary_type','nickname',
        'verification_status','daily_limit','is_active',
    ];
    protected $casts = ['daily_limit'=>'decimal:2','is_active'=>'boolean'];
    public function customer() { return $this->belongsTo(Customer::class); }
}
