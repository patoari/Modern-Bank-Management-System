<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'category', 'description',
        'min_opening_balance', 'min_balance', 'interest_rate',
        'interest_calculation_method', 'interest_posting_frequency',
        'allow_cheque_book', 'allow_overdraft', 'overdraft_limit',
        'is_active',
    ];

    protected $casts = [
        'min_opening_balance'  => 'decimal:2',
        'min_balance'          => 'decimal:2',
        'interest_rate'        => 'decimal:4',
        'overdraft_limit'      => 'decimal:2',
        'allow_cheque_book'    => 'boolean',
        'allow_overdraft'      => 'boolean',
        'is_active'            => 'boolean',
    ];

    public function accounts() { return $this->hasMany(Account::class); }
}
