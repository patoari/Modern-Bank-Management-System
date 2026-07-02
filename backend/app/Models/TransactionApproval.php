<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionApproval extends Model
{
    protected $fillable = [
        'transaction_id','level','approver_id','status','comments','approved_at',
    ];
    protected $casts = ['approved_at'=>'datetime'];
    public function transaction() { return $this->belongsTo(Transaction::class); }
    public function approver()    { return $this->belongsTo(User::class,'approver_id'); }
}
