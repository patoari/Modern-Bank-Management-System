<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Users
            'users.view','users.create','users.update','users.delete','users.assign_roles',
            // Branches
            'branches.view','branches.create','branches.update','branches.delete','branches.manage_all',
            // Customers
            'customers.view','customers.create','customers.update','customers.delete','customers.kyc_verify',
            // Accounts
            'accounts.view','accounts.create','accounts.update','accounts.close','accounts.freeze','accounts.view_balance',
            // Transactions
            'transactions.view','transactions.create','transactions.approve','transactions.reverse',
            'transactions.cash_deposit','transactions.cash_withdrawal','transactions.fund_transfer',
            // Loans
            'loans.view','loans.create','loans.assess','loans.approve','loans.disburse',
            'loans.monitor','loans.set_credit_limit','loans.restructure',
            // Cards
            'cards.view','cards.issue','cards.block','cards.set_limits',
            // Cheques
            'cheques.view','cheques.issue','cheques.stop_payment','cheques.clear',
            // Deposits
            'deposits.view','deposits.create','deposits.premature_close','deposits.renew',
            // Accounting
            'accounting.view_ledger','accounting.create_entry','accounting.reconciliation','accounting.tax_calculation',
            // Reports
            'reports.customer','reports.transaction','reports.loan','reports.financial',
            'reports.regulatory','reports.branch','reports.audit','reports.export',
            // Audit & Compliance
            'audit.view_logs','audit.compliance_check','audit.aml_monitoring','audit.str_filing','audit.fraud_detection',
            // System
            'system.view_settings','system.update_settings','system.backup','system.maintenance','system.manage_api_keys',
            // Notifications
            'notifications.send','notifications.view_queue','notifications.configure',
            // Cash
            'cash.teller_position','cash.branch_vault','cash.atm_replenishment',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'sanctum']);
        }

        $roles = [
            'super_admin' => $permissions, // all
            'bank_admin'  => array_diff($permissions, ['system.backup','system.maintenance','system.manage_api_keys']),
            'branch_manager' => [
                'users.view','users.update',
                'branches.view','branches.update',
                'customers.view','customers.create','customers.update','customers.kyc_verify',
                'accounts.view','accounts.create','accounts.update','accounts.close','accounts.freeze','accounts.view_balance',
                'transactions.view','transactions.create','transactions.approve','transactions.cash_deposit',
                'transactions.cash_withdrawal','transactions.fund_transfer',
                'loans.view','loans.create','loans.assess',
                'cards.view','cards.issue','cards.block',
                'cheques.view','cheques.issue',
                'deposits.view','deposits.create',
                'reports.customer','reports.transaction','reports.loan','reports.financial','reports.branch','reports.export',
                'audit.view_logs','cash.teller_position','cash.branch_vault',
            ],
            'teller' => [
                'accounts.view','accounts.view_balance',
                'transactions.view','transactions.create','transactions.cash_deposit',
                'transactions.cash_withdrawal','transactions.fund_transfer',
                'cheques.view','deposits.view',
                'cash.teller_position',
            ],
            'customer_service' => [
                'customers.view','customers.create','customers.update','customers.kyc_verify',
                'accounts.view','accounts.create','accounts.update','accounts.view_balance',
                'transactions.view','transactions.cash_deposit','transactions.fund_transfer',
                'cards.view','cards.issue','cards.block',
                'cheques.view','cheques.issue',
                'deposits.view','deposits.create',
            ],
            'loan_officer' => [
                'customers.view',
                'accounts.view',
                'loans.view','loans.create','loans.assess','loans.monitor',
                'reports.loan',
            ],
            'credit_manager' => [
                'customers.view',
                'accounts.view',
                'loans.view','loans.create','loans.assess','loans.approve','loans.disburse',
                'loans.monitor','loans.set_credit_limit','loans.restructure',
                'reports.loan','reports.financial',
                'audit.view_logs',
            ],
            'accountant' => [
                'accounts.view','accounts.view_balance',
                'transactions.view',
                'accounting.view_ledger','accounting.create_entry','accounting.reconciliation','accounting.tax_calculation',
                'reports.financial','reports.transaction','reports.export',
                'audit.view_logs',
            ],
            'auditor' => [
                'customers.view','accounts.view','transactions.view',
                'loans.view','cards.view','deposits.view',
                'audit.view_logs','audit.compliance_check','audit.fraud_detection',
                'reports.customer','reports.transaction','reports.loan','reports.financial',
                'reports.regulatory','reports.branch','reports.audit',
            ],
            'compliance_officer' => [
                'customers.view','accounts.view','transactions.view',
                'audit.view_logs','audit.compliance_check','audit.aml_monitoring','audit.str_filing','audit.fraud_detection',
                'reports.regulatory','reports.audit','reports.export',
            ],
            'it_admin' => [
                'users.view','users.create','users.update','users.delete','users.assign_roles',
                'branches.view',
                'system.view_settings','system.update_settings','system.backup',
                'system.maintenance','system.manage_api_keys',
                'audit.view_logs',
            ],
            'customer' => [
                'accounts.view','accounts.view_balance',
                'transactions.view','transactions.fund_transfer',
                'loans.view','cards.view','deposits.view',
                'reports.customer',
                'notifications.configure',
            ],
            'business_customer' => [
                'accounts.view','accounts.view_balance',
                'transactions.view','transactions.fund_transfer',
                'loans.view','cards.view','deposits.view',
                'reports.customer',
                'notifications.configure',
            ],
        ];

        foreach ($roles as $roleName => $rolePerms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
            $role->syncPermissions(array_values($rolePerms));
        }
    }
}
