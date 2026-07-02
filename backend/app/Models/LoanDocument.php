<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanDocument extends Model
{
    protected $fillable = [
        'loan_id','document_type','file_path','file_name',
        'verification_status','uploaded_by','verified_by','verified_at',
    ];
    protected $casts = ['verified_at'=>'datetime'];
    public function loan()       { return $this->belongsTo(Loan::class); }
    public function uploadedBy() { return $this->belongsTo(User::class,'uploaded_by'); }
}
