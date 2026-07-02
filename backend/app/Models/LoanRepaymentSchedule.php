<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRepaymentSchedule extends Model
{
    protected $fillable = [
        'loan_id','installment_number','due_date',
        'principal_amount','interest_amount','penalty_amount','emi_amount',
        'paid_amount','paid_date','payment_status','transaction_id',
    ];
    protected $casts = [
        'due_date'=>'date','paid_date'=>'date',
        'principal_amount'=>'decimal:2','interest_amount'=>'decimal:2',
        'penalty_amount'=>'decimal:2','emi_amount'=>'decimal:2','paid_amount'=>'decimal:2',
    ];
    public function loan()        { return $this->belongsTo(Loan::class); }
    public function transaction() { return $this->belongsTo(Transaction::class); }
}
