<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardProduct extends Model
{
    protected $fillable = [
        'product_code','product_name','card_type','card_network',
        'annual_fee','joining_fee','reward_rate','cashback_rate',
        'default_credit_limit','default_daily_limit','default_per_txn_limit',
        'is_active','description',
    ];
    protected $casts = [
        'annual_fee'=>'decimal:2','joining_fee'=>'decimal:2',
        'reward_rate'=>'decimal:4','cashback_rate'=>'decimal:4',
        'default_credit_limit'=>'decimal:2','default_daily_limit'=>'decimal:2',
        'default_per_txn_limit'=>'decimal:2','is_active'=>'boolean',
    ];
    public function cards() { return $this->hasMany(Card::class); }
}
