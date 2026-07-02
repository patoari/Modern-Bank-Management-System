<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Atm extends Model
{
    protected $fillable = [
        'atm_id','atm_name','branch_id','location_type','address',
        'city','state','latitude','longitude',
        'status','cash_available','last_cash_replenishment',
    ];
    protected $casts = [
        'cash_available'=>'decimal:2',
        'last_cash_replenishment'=>'datetime',
        'latitude'=>'decimal:8','longitude'=>'decimal:8',
    ];
    public function branch() { return $this->belongsTo(Branch::class); }
}
