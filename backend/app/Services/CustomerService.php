<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerService
{
    public function list(array $filters, int $perPage = 15)
    {
        $query = Customer::with(['user', 'addresses'])
            ->whereNull('deleted_at');

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('customer_id', 'like', "%{$s}%")
                  ->orWhereHas('user', fn($u) => $u->where('email', 'like', "%{$s}%")
                                                    ->orWhere('phone', 'like', "%{$s}%"));
            });
        }
        if (!empty($filters['kyc_status']))    $query->where('kyc_status', $filters['kyc_status']);
        if (!empty($filters['segment']))       $query->where('segment', $filters['segment']);
        if (!empty($filters['risk_rating']))   $query->where('risk_rating', $filters['risk_rating']);
        if (!empty($filters['customer_type'])) $query->where('customer_type', $filters['customer_type']);

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data, int $createdBy): Customer
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $user = User::create([
                'uuid'          => Str::uuid(),
                'email'         => $data['email'],
                'phone'         => $data['phone'] ?? null,
                'password_hash' => Hash::make($data['password'] ?? Str::random(12)),
                'user_type'     => 'customer',
                'status'        => 'active',
            ]);
            $user->assignRole('customer');

            $customer = Customer::create([
                'user_id'        => $user->id,
                'customer_id'    => $this->generateCustomerId(),
                'customer_type'  => $data['customer_type'] ?? 'individual',
                'segment'        => $data['segment'] ?? 'retail',
                'first_name'     => $data['first_name'],
                'last_name'      => $data['last_name'],
                'date_of_birth'  => $data['date_of_birth'] ?? null,
                'gender'         => $data['gender'] ?? null,
                'nationality'    => $data['nationality'] ?? null,
                'occupation'     => $data['occupation'] ?? null,
                'annual_income'  => $data['annual_income'] ?? null,
                'source_of_funds'=> $data['source_of_funds'] ?? null,
                'kyc_status'     => 'pending',
                'risk_rating'    => 'low',
                'customer_since' => now()->toDateString(),
            ]);

            if (!empty($data['address'])) {
                $customer->addresses()->create(array_merge($data['address'], ['is_primary' => true]));
            }

            AuditLog::create([
                'user_id'      => $createdBy,
                'action'       => 'create',
                'module'       => 'customers',
                'record_id'    => $customer->id,
                'record_type'  => Customer::class,
                'new_values'   => $customer->toArray(),
                'status'       => 'success',
                'description'  => "Customer {$customer->customer_id} created",
                'created_at'   => now(),
            ]);

            return $customer->load(['user', 'addresses']);
        });
    }

    public function update(Customer $customer, array $data, int $updatedBy): Customer
    {
        $old = $customer->toArray();
        $customer->update($data);
        if (isset($data['email']) || isset($data['phone'])) {
            $customer->user->update(array_filter([
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]));
        }
        AuditLog::create([
            'user_id'    => $updatedBy,
            'action'     => 'update',
            'module'     => 'customers',
            'record_id'  => $customer->id,
            'record_type'=> Customer::class,
            'old_values' => $old,
            'new_values' => $customer->fresh()->toArray(),
            'status'     => 'success',
            'description'=> "Customer {$customer->customer_id} updated",
            'created_at' => now(),
        ]);
        return $customer->fresh(['user', 'addresses', 'accounts']);
    }

    public function updateKyc(Customer $customer, string $status, int $updatedBy): Customer
    {
        $customer->update(['kyc_status' => $status]);
        AuditLog::create([
            'user_id'    => $updatedBy,
            'action'     => 'kyc_update',
            'module'     => 'customers',
            'record_id'  => $customer->id,
            'record_type'=> Customer::class,
            'new_values' => ['kyc_status' => $status],
            'status'     => 'success',
            'description'=> "KYC status updated to {$status} for {$customer->customer_id}",
            'created_at' => now(),
        ]);
        return $customer;
    }

    private function generateCustomerId(): string
    {
        $prefix = 'CUST';
        $number = str_pad(Customer::withTrashed()->count() + 1, 8, '0', STR_PAD_LEFT);
        return $prefix . $number;
    }
}
