<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycDocument extends Model
{
    protected $fillable = [
        'customer_id','document_type','document_number','file_path',
        'verification_status','verified_at','verified_by','expiry_date','rejection_reason',
    ];
    protected $casts = ['verified_at'=>'datetime','expiry_date'=>'date'];
    public function customer()   { return $this->belongsTo(Customer::class); }
    public function verifiedBy() { return $this->belongsTo(User::class,'verified_by'); }
}
