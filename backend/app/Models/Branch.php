<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_code', 'branch_name', 'branch_type', 'ifsc_code', 'swift_code',
        'email', 'phone', 'address_line1', 'address_line2', 'city', 'state',
        'country', 'postal_code', 'status', 'opened_date', 'manager_id',
    ];

    protected $casts = ['opened_date' => 'date'];

    public function staff()        { return $this->hasMany(Staff::class); }
    public function accounts()     { return $this->hasMany(Account::class); }
    public function transactions() { return $this->hasMany(Transaction::class); }
    public function loans()        { return $this->hasMany(Loan::class); }
    public function atms()         { return $this->hasMany(Atm::class); }
    public function manager()      { return $this->belongsTo(Staff::class, 'manager_id'); }
}
