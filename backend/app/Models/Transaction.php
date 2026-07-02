<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_ref', 'transaction_type', 'transaction_mode',
        'from_account_id', 'to_account_id', 'amount', 'currency_code',
        'exchange_rate', 'transaction_fee', 'gst_amount', 'total_amount',
        'transaction_status', 'description', 'narration',
        'initiated_by', 'approved_by', 'branch_id',
        'value_date', 'posting_date',
        'requires_approval', 'approval_status',
        'reversal_of', 'is_reversed',
        'channel', 'ip_address', 'device_info',
    ];

    protected $casts = [
        'amount'            => 'decimal:2',
        'exchange_rate'     => 'decimal:6',
        'transaction_fee'   => 'decimal:2',
        'gst_amount'        => 'decimal:2',
        'total_amount'      => 'decimal:2',
        'value_date'        => 'date',
        'posting_date'      => 'datetime',
        'requires_approval' => 'boolean',
        'is_reversed'       => 'boolean',
    ];

    public function fromAccount()  { return $this->belongsTo(Account::class, 'from_account_id'); }
    public function toAccount()    { return $this->belongsTo(Account::class, 'to_account_id'); }
    public function initiatedBy()  { return $this->belongsTo(User::class, 'initiated_by'); }
    public function approvedBy()   { return $this->belongsTo(User::class, 'approved_by'); }
    public function branch()       { return $this->belongsTo(Branch::class); }
    public function approvals()    { return $this->hasMany(TransactionApproval::class); }
    public function amlAlerts()    { return $this->hasMany(AmlAlert::class); }
    public function reversalOf()   { return $this->belongsTo(Transaction::class, 'reversal_of'); }
}
