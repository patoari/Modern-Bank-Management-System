<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StandingInstruction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'from_account_id', 'to_account_id', 'beneficiary_id', 'instruction_name',
        'amount', 'currency_code', 'frequency', 'debit_type', 'max_amount',
        'start_date', 'end_date', 'status', 'remarks', 'last_execution_at',
        'next_execution_at', 'executed_count',
    ];

    protected $casts = [
        'amount' => 'decimal:18,2',
        'max_amount' => 'decimal:18,2',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_execution_at' => 'datetime',
        'next_execution_at' => 'datetime',
    ];

    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
