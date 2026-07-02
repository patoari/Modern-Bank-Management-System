<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    protected $fillable = [
        'product_code','product_name','product_type','description',
        'min_amount','max_amount','min_tenure_months','max_tenure_months',
        'base_interest_rate','processing_fee_type','processing_fee_value',
        'is_active','requires_collateral','prepayment_allowed','prepayment_penalty_rate',
    ];
    protected $casts = [
        'min_amount'=>'decimal:2','max_amount'=>'decimal:2',
        'base_interest_rate'=>'decimal:4','processing_fee_value'=>'decimal:4',
        'prepayment_penalty_rate'=>'decimal:4',
        'is_active'=>'boolean','requires_collateral'=>'boolean','prepayment_allowed'=>'boolean',
    ];
    public function loans() { return $this->hasMany(Loan::class); }
}
