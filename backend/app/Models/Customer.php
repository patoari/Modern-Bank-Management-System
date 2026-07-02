<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'customer_id', 'customer_type', 'segment',
        'first_name', 'last_name', 'date_of_birth', 'gender',
        'nationality', 'occupation', 'annual_income', 'source_of_funds',
        'kyc_status', 'risk_rating', 'is_pep', 'aml_status',
        'customer_since', 'customer_lifetime_value', 'relationship_manager_id',
        'communication_preference', 'is_deceased', 'is_dormant',
    ];

    protected $casts = [
        'date_of_birth'            => 'date',
        'customer_since'           => 'date',
        'annual_income'            => 'decimal:2',
        'customer_lifetime_value'  => 'decimal:2',
        'is_pep'                   => 'boolean',
        'is_deceased'              => 'boolean',
        'is_dormant'               => 'boolean',
    ];

    public function user()          { return $this->belongsTo(User::class); }
    public function addresses()     { return $this->hasMany(CustomerAddress::class); }
    public function kycDocuments()  { return $this->hasMany(KycDocument::class); }
    public function accounts()      { return $this->hasMany(Account::class); }
    public function loans()         { return $this->hasMany(Loan::class); }
    public function cards()         { return $this->hasMany(Card::class); }
    public function fixedDeposits() { return $this->hasMany(FixedDeposit::class); }
    public function complaints()    { return $this->hasMany(CustomerComplaint::class); }
    public function amlAlerts()     { return $this->hasMany(AmlAlert::class); }
    public function beneficiaries() { return $this->hasMany(Beneficiary::class); }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
