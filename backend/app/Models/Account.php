<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_number', 'account_type_id', 'customer_id', 'branch_id',
        'currency_code', 'account_title', 'account_status', 'opening_date',
        'available_balance', 'ledger_balance', 'hold_amount', 'accrued_interest',
        'interest_rate', 'last_transaction_date', 'is_joint_account',
        'minimum_balance', 'dormancy_date', 'closed_date', 'closed_by',
    ];

    protected $casts = [
        'opening_date'         => 'date',
        'last_transaction_date' => 'date',
        'dormancy_date'        => 'date',
        'closed_date'          => 'date',
        'available_balance'    => 'decimal:2',
        'ledger_balance'       => 'decimal:2',
        'hold_amount'          => 'decimal:2',
        'accrued_interest'     => 'decimal:2',
        'interest_rate'        => 'decimal:4',
        'minimum_balance'      => 'decimal:2',
        'is_joint_account'     => 'boolean',
    ];

    public function accountType()         { return $this->belongsTo(AccountType::class); }
    public function customer()            { return $this->belongsTo(Customer::class); }
    public function branch()              { return $this->belongsTo(Branch::class); }
    public function outgoingTransactions(){ return $this->hasMany(Transaction::class, 'from_account_id'); }
    public function incomingTransactions(){ return $this->hasMany(Transaction::class, 'to_account_id'); }
    public function standingInstructions(){ return $this->hasMany(StandingInstruction::class); }
    public function chequeBooks()         { return $this->hasMany(ChequeBook::class); }
    public function jointHolders()        { return $this->hasMany(JointAccountHolder::class); }
}
