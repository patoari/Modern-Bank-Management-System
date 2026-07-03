<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Atm extends Model
{
    protected $fillable = [
        'atm_id', 'branch_id', 'location_name', 'latitude', 'longitude',
        'address', 'city', 'state', 'postal_code', 'landmark',
        'atm_type', 'status', 'opening_time', 'closing_time', 'operates_24_7',
        'cash_available', 'last_cash_refill', 'installation_date',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'operates_24_7' => 'boolean',
        'last_cash_refill' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}

