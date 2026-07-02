<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id','address_type','address_line1','address_line2',
        'city','state','country','postal_code','is_primary',
    ];
    protected $casts = ['is_primary' => 'boolean'];
    public function customer() { return $this->belongsTo(Customer::class); }
}
