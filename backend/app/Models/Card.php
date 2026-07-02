<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'card_number', 'card_product_id', 'customer_id', 'account_id',
        'card_type', 'card_network', 'card_status', 'card_holder_name',
        'expiry_date', 'issue_date', 'activation_date',
        'credit_limit', 'available_limit', 'outstanding_amount',
        'billing_cycle_day', 'due_date', 'minimum_payment',
        'reward_points', 'international_enabled', 'online_enabled',
        'contactless_enabled', 'daily_limit', 'per_transaction_limit',
        'pin_change_required', 'blocked_reason', 'blocked_at',
    ];

    protected $casts = [
        'expiry_date'          => 'date',
        'issue_date'           => 'date',
        'activation_date'      => 'date',
        'due_date'             => 'date',
        'blocked_at'           => 'datetime',
        'credit_limit'         => 'decimal:2',
        'available_limit'      => 'decimal:2',
        'outstanding_amount'   => 'decimal:2',
        'minimum_payment'      => 'decimal:2',
        'daily_limit'          => 'decimal:2',
        'per_transaction_limit'=> 'decimal:2',
        'international_enabled'=> 'boolean',
        'online_enabled'       => 'boolean',
        'contactless_enabled'  => 'boolean',
        'pin_change_required'  => 'boolean',
    ];

    protected $hidden = ['card_number'];

    public function customer()    { return $this->belongsTo(Customer::class); }
    public function account()     { return $this->belongsTo(Account::class); }
    public function cardProduct() { return $this->belongsTo(CardProduct::class); }

    public function getMaskedCardNumberAttribute(): string
    {
        return '****-****-****-' . substr($this->card_number, -4);
    }
}
