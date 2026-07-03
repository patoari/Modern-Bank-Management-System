<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cheque extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cheque_number', 'cheque_book_id', 'account_id', 'status',
        'issued_at', 'cleared_at', 'payee_name', 'amount',
        'stop_reason', 'stopped_at', 'stopped_by', 'bounce_reason', 'bounced_at',
    ];

    protected $casts = [
        'amount' => 'decimal:18,2',
        'issued_at' => 'datetime',
        'cleared_at' => 'datetime',
        'stopped_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function chequeBook()
    {
        return $this->belongsTo(ChequeBook::class);
    }
}
