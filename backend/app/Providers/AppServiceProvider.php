<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\User;
use App\Policies\AccountPolicy;
use App\Policies\BranchPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\LoanPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register policies
        Gate::policy(Customer::class,    CustomerPolicy::class);
        Gate::policy(Account::class,     AccountPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Loan::class,        LoanPolicy::class);
        Gate::policy(User::class,        UserPolicy::class);
        Gate::policy(Branch::class,      BranchPolicy::class);

        // Audit gates (model-less ability checks)
        Gate::define('viewLogs',   fn(User $u) => $u->hasPermissionTo('audit.view_logs'));
        Gate::define('viewAml',    fn(User $u) => $u->hasPermissionTo('audit.aml_monitoring'));
        Gate::define('reviewAml',  fn(User $u) => $u->hasPermissionTo('audit.aml_monitoring'));

        // Super admin bypasses all gates
        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->hasRole('super_admin')) {
                return true;
            }
            return null;
        });

        // Sanctum uses 'sanctum' guard — tell it which model to use
        Sanctum::usePersonalAccessTokenModel(\Laravel\Sanctum\PersonalAccessToken::class);
    }
}
