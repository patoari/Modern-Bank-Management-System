<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'employee_id', 'branch_id', 'designation', 'department',
        'date_of_joining', 'employment_type', 'salary', 'supervisor_id',
    ];

    protected $casts = [
        'date_of_joining' => 'date',
        'salary'          => 'decimal:2',
    ];

    public function user()       { return $this->belongsTo(User::class); }
    public function branch()     { return $this->belongsTo(Branch::class); }
    public function supervisor() { return $this->belongsTo(Staff::class, 'supervisor_id'); }
}
