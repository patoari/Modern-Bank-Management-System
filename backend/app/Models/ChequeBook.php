<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChequeBook extends Model
{
    protected $fillable = [
        'cheque_book_number','account_id','number_of_leaves',
        'start_cheque_number','end_cheque_number','issue_date','status',
    ];
    protected $casts = ['issue_date'=>'date'];
    public function account()  { return $this->belongsTo(Account::class); }
    public function cheques()  { return $this->hasMany(Cheque::class); }
}
