<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_account_number', 'loan_product_id', 'customer_id', 'branch_id',
        'principal_amount', 'sanctioned_amount', 'disbursed_amount',
        'interest_rate', 'interest_type', 'tenure_months', 'emi_amount',
        'application_date', 'sanction_date', 'disbursement_date', 'maturity_date',
        'outstanding_principal', 'outstanding_interest', 'total_outstanding',
        'loan_status', 'npa_classification', 'overdue_days', 'penalty_amount',
        'repayment_account_id', 'loan_officer_id', 'credit_manager_id',
        'purpose', 'collateral_type', 'collateral_value',
        'credit_score', 'rejection_reason',
        'prepayment_charges', 'foreclosure_amount',
    ];

    protected $casts = [
        'principal_amount'     => 'decimal:2',
        'sanctioned_amount'    => 'decimal:2',
        'disbursed_amount'     => 'decimal:2',
        'interest_rate'        => 'decimal:4',
        'emi_amount'           => 'decimal:2',
        'outstanding_principal'=> 'decimal:2',
        'outstanding_interest' => 'decimal:2',
        'total_outstanding'    => 'decimal:2',
        'penalty_amount'       => 'decimal:2',
        'collateral_value'     => 'decimal:2',
        'prepayment_charges'   => 'decimal:2',
        'foreclosure_amount'   => 'decimal:2',
        'application_date'     => 'date',
        'sanction_date'        => 'date',
        'disbursement_date'    => 'date',
        'maturity_date'        => 'date',
    ];

    public function customer()          { return $this->belongsTo(Customer::class); }
    public function branch()            { return $this->belongsTo(Branch::class); }
    public function loanProduct()       { return $this->belongsTo(LoanProduct::class); }
    public function repaymentAccount()  { return $this->belongsTo(Account::class, 'repayment_account_id'); }
    public function loanOfficer()       { return $this->belongsTo(Staff::class, 'loan_officer_id'); }
    public function repaymentSchedule() { return $this->hasMany(LoanRepaymentSchedule::class); }
    public function documents()         { return $this->hasMany(LoanDocument::class); }
}
