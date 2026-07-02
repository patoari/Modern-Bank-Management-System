<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    protected $fillable = [
        'cheque_number','account_id','cheque_book_id','amount',
        'payee_name','cheque_date','presentation_date','clearance_date',
        'status','transaction_id','bounce_reason','stop_reason',
    ];
    protected $casts = [
        'amount'=>'decimal:2',
        'cheque_date'=>'date','presentation_date'=>'date','clearance_date'=>'date',
    ];
    public function account()     { return $this->belongsTo(Account::class); }
    public function chequeBook()  { return $this->belongsTo(ChequeBook::class); }
    public function transaction() { return $this->belongsTo(Transaction::class); }
}
