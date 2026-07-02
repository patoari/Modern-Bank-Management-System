<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepositProduct extends Model
{
    protected $fillable = [
        'product_code','product_name','product_type','description',
        'min_amount','max_amount','min_tenure_days','max_tenure_days',
        'interest_rate','premature_penalty_rate','is_active','tds_applicable',
    ];
    protected $casts = [
        'min_amount'=>'decimal:2','max_amount'=>'decimal:2',
        'interest_rate'=>'decimal:4','premature_penalty_rate'=>'decimal:4',
        'is_active'=>'boolean','tds_applicable'=>'boolean',
    ];
    public function fixedDeposits() { return $this->hasMany(FixedDeposit::class); }
}
