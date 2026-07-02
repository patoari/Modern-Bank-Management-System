<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandingInstruction extends Model
{
    protected $fillable = [
        'instruction_number','account_id','instruction_type',
        'beneficiary_account_number','beneficiary_name','ifsc_code',
        'amount','frequency','start_date','end_date','next_execution_date',
        'status','description','execution_count','last_executed_at',
    ];
    protected $casts = [
        'amount'=>'decimal:2',
        'start_date'=>'date','end_date'=>'date','next_execution_date'=>'date',
        'last_executed_at'=>'datetime',
    ];
    public function account() { return $this->belongsTo(Account::class); }
}
