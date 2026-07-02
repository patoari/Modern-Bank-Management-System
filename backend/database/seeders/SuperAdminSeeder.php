<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\User;
use App\Models\AccountType;
use App\Models\LoanProduct;
use App\Models\CardProduct;
use App\Models\DepositProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@bank.com'],
            [
                'uuid'                 => Str::uuid(),
                'phone'                => '+1-000-000-0001',
                'password_hash'        => Hash::make('Admin@1234567', ['rounds' => 10]),
                'user_type'            => 'admin',
                'status'               => 'active',
                'email_verified_at'    => now(),
                'force_password_change'=> false,
            ]
        );
        // Always ensure hash is correct (handles re-seed edge cases)
        if (!Hash::check('Admin@1234567', $admin->password_hash)) {
            $admin->update(['password_hash' => Hash::make('Admin@1234567', ['rounds' => 10])]);
        }
        $admin->assignRole('super_admin');

        // Head Office Branch
        $branch = Branch::firstOrCreate(
            ['branch_code' => 'HO001'],
            [
                'branch_name'  => 'Head Office',
                'branch_type'  => 'main',
                'ifsc_code'    => 'BANK0HO001',
                'swift_code'   => 'BANKUSHO',
                'email'        => 'ho@bank.com',
                'phone'        => '+1-000-000-0002',
                'address_line1'=> '123 Banking Street',
                'city'         => 'New York',
                'state'        => 'NY',
                'country'      => 'USA',
                'postal_code'  => '10001',
                'status'       => 'active',
                'opened_date'  => '2020-01-01',
            ]
        );

        // Staff record for admin
        Staff::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'employee_id'     => 'EMP000001',
                'branch_id'       => $branch->id,
                'designation'     => 'Super Administrator',
                'department'      => 'Management',
                'date_of_joining' => '2020-01-01',
                'employment_type' => 'full_time',
            ]
        );

        // Account Types
        $accountTypes = [
            ['code'=>'SAV001','name'=>'Regular Savings','category'=>'savings','min_opening_balance'=>500,'min_balance'=>500,'interest_rate'=>3.5,'allow_cheque_book'=>false],
            ['code'=>'SAV002','name'=>'Premium Savings','category'=>'savings','min_opening_balance'=>10000,'min_balance'=>5000,'interest_rate'=>4.5,'allow_cheque_book'=>true],
            ['code'=>'CUR001','name'=>'Current Account','category'=>'current','min_opening_balance'=>1000,'min_balance'=>0,'interest_rate'=>0,'allow_cheque_book'=>true,'allow_overdraft'=>true],
            ['code'=>'SAL001','name'=>'Salary Account','category'=>'salary','min_opening_balance'=>0,'min_balance'=>0,'interest_rate'=>3.0,'allow_cheque_book'=>false],
        ];
        foreach ($accountTypes as $at) {
            AccountType::firstOrCreate(['code' => $at['code']], array_merge($at, ['is_active' => true]));
        }

        // Loan Products
        $loanProducts = [
            ['product_code'=>'HL001','product_name'=>'Home Loan','product_type'=>'home','min_amount'=>100000,'max_amount'=>10000000,'min_tenure_months'=>60,'max_tenure_months'=>300,'base_interest_rate'=>8.5,'requires_collateral'=>true],
            ['product_code'=>'PL001','product_name'=>'Personal Loan','product_type'=>'personal','min_amount'=>10000,'max_amount'=>500000,'min_tenure_months'=>12,'max_tenure_months'=>60,'base_interest_rate'=>12.0,'requires_collateral'=>false],
            ['product_code'=>'AL001','product_name'=>'Auto Loan','product_type'=>'auto','min_amount'=>50000,'max_amount'=>2000000,'min_tenure_months'=>12,'max_tenure_months'=>84,'base_interest_rate'=>9.5,'requires_collateral'=>true],
            ['product_code'=>'BL001','product_name'=>'Business Loan','product_type'=>'business','min_amount'=>50000,'max_amount'=>5000000,'min_tenure_months'=>12,'max_tenure_months'=>120,'base_interest_rate'=>11.0,'requires_collateral'=>false],
        ];
        foreach ($loanProducts as $lp) {
            LoanProduct::firstOrCreate(['product_code' => $lp['product_code']], array_merge($lp, ['is_active'=>true,'prepayment_allowed'=>true]));
        }

        // Card Products
        $cardProducts = [
            ['product_code'=>'DC001','product_name'=>'Classic Debit Card','card_type'=>'debit','card_network'=>'visa','annual_fee'=>0,'default_daily_limit'=>5000,'default_per_txn_limit'=>2000],
            ['product_code'=>'CC001','product_name'=>'Platinum Credit Card','card_type'=>'credit','card_network'=>'mastercard','annual_fee'=>999,'default_credit_limit'=>100000,'cashback_rate'=>1.5],
        ];
        foreach ($cardProducts as $cp) {
            CardProduct::firstOrCreate(['product_code' => $cp['product_code']], array_merge($cp, ['is_active'=>true]));
        }

        // Deposit Products
        $depositProducts = [
            ['product_code'=>'FD001','product_name'=>'Standard FD','product_type'=>'fd','min_amount'=>1000,'max_amount'=>null,'min_tenure_days'=>7,'max_tenure_days'=>3650,'interest_rate'=>7.0,'premature_penalty_rate'=>1.0],
            ['product_code'=>'FD002','product_name'=>'Senior Citizen FD','product_type'=>'fd','min_amount'=>1000,'max_amount'=>null,'min_tenure_days'=>180,'max_tenure_days'=>3650,'interest_rate'=>7.5,'premature_penalty_rate'=>0.5],
        ];
        foreach ($depositProducts as $dp) {
            DepositProduct::firstOrCreate(['product_code' => $dp['product_code']], array_merge($dp, ['is_active'=>true,'tds_applicable'=>true]));
        }
    }
}
