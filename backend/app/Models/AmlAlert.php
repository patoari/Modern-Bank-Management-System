<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmlAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_number', 'customer_id', 'transaction_id', 'account_id',
        'alert_type', 'severity', 'alert_status',
        'rule_triggered', 'description', 'amount_involved',
        'assigned_to', 'reviewed_by', 'reviewed_at',
        'resolution_notes', 'str_filed', 'str_reference',
    ];

    protected $casts = [
        'amount_involved' => 'decimal:2',
        'reviewed_at'     => 'datetime',
        'str_filed'       => 'boolean',
    ];

    public function customer()    { return $this->belongsTo(Customer::class); }
    public function transaction()  { return $this->belongsTo(Transaction::class); }
    public function account()     { return $this->belongsTo(Account::class); }
    public function assignedTo()  { return $this->belongsTo(User::class, 'assigned_to'); }
    public function reviewedBy()  { return $this->belongsTo(User::class, 'reviewed_by'); }
}
