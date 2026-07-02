<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'fd_account_number', 'deposit_product_id', 'customer_id', 'branch_id', 'account_id',
        'principal_amount', 'interest_rate', 'tenure_days', 'tenure_months',
        'maturity_amount', 'interest_amount', 'accrued_interest',
        'start_date', 'maturity_date', 'fd_status',
        'is_auto_renewal', 'renewal_instructions', 'premature_closure_date',
        'premature_penalty', 'payout_frequency', 'payout_account_id',
        'tds_applicable', 'tds_rate', 'tds_deducted',
    ];

    protected $casts = [
        'principal_amount'        => 'decimal:2',
        'interest_rate'           => 'decimal:4',
        'maturity_amount'         => 'decimal:2',
        'interest_amount'         => 'decimal:2',
        'accrued_interest'        => 'decimal:2',
        'premature_penalty'       => 'decimal:2',
        'tds_rate'                => 'decimal:4',
        'tds_deducted'            => 'decimal:2',
        'start_date'              => 'date',
        'maturity_date'           => 'date',
        'premature_closure_date'  => 'date',
        'is_auto_renewal'         => 'boolean',
        'tds_applicable'          => 'boolean',
    ];

    public function customer()        { return $this->belongsTo(Customer::class); }
    public function branch()          { return $this->belongsTo(Branch::class); }
    public function depositProduct()  { return $this->belongsTo(DepositProduct::class); }
    public function linkedAccount()   { return $this->belongsTo(Account::class, 'account_id'); }
    public function payoutAccount()   { return $this->belongsTo(Account::class, 'payout_account_id'); }
}
