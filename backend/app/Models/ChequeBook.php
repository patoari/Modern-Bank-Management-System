<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChequeBook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cheque_book_number', 'account_id', 'from_cheque_no', 'to_cheque_no',
        'status', 'issued_date', 'expiry_date', 'delivery_mode', 'delivery_address',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function cheques()
    {
        return $this->hasMany(Cheque::class);
    }
}
