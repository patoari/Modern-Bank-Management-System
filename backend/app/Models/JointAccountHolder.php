<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JointAccountHolder extends Model
{
    protected $fillable = [
        'account_id','customer_id','is_primary','authorization_level','added_date',
    ];
    protected $casts = ['is_primary'=>'boolean','added_date'=>'date'];
    public function account()  { return $this->belongsTo(Account::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}
