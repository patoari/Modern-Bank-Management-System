<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('email')->unique();
            $table->string('phone', 20)->unique()->nullable();
            $table->string('password_hash');
            $table->enum('user_type', ['customer','staff','admin']);
            $table->enum('status', ['active','inactive','suspended','locked','pending_verification'])->default('pending_verification');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->boolean('force_password_change')->default(false);
            $table->boolean('two_factor_enabled')->default(false);
            $table->enum('two_factor_type', ['sms','email','totp','hardware_token'])->nullable();
            $table->string('two_factor_secret')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Branches
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_code', 20)->unique();
            $table->string('branch_name');
            $table->enum('branch_type', ['main','regional','urban','rural','atm']);
            $table->string('ifsc_code', 20)->unique();
            $table->string('swift_code', 20)->nullable();
            $table->string('email');
            $table->string('phone', 20);
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('country', 100)->default('US');
            $table->string('postal_code', 20)->nullable();
            $table->enum('status', ['active','inactive','under_renovation'])->default('active');
            $table->date('opened_date')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->timestamps();
        });

        // Staff
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_id', 50)->unique();
            $table->foreignId('branch_id')->constrained();
            $table->string('designation');
            $table->string('department')->nullable();
            $table->date('date_of_joining');
            $table->enum('employment_type', ['full_time','part_time','contract','intern'])->default('full_time');
            $table->decimal('salary', 12, 2)->nullable();
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('customer_id', 20)->unique();
            $table->enum('customer_type', ['individual','business','corporate'])->default('individual');
            $table->enum('segment', ['retail','sme','corporate','hni','premier'])->default('retail');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male','female','other'])->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->decimal('annual_income', 15, 2)->nullable();
            $table->string('source_of_funds')->nullable();
            $table->enum('kyc_status', ['pending','under_review','approved','rejected'])->default('pending');
            $table->enum('risk_rating', ['low','medium','high','very_high'])->default('low');
            $table->boolean('is_pep')->default(false);
            $table->enum('aml_status', ['clear','under_review','flagged','blacklisted'])->default('clear');
            $table->date('customer_since')->nullable();
            $table->decimal('customer_lifetime_value', 15, 2)->default(0);
            $table->unsignedBigInteger('relationship_manager_id')->nullable();
            $table->enum('communication_preference', ['sms','email','push','none'])->default('email');
            $table->boolean('is_deceased')->default(false);
            $table->boolean('is_dormant')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Customer Addresses
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('address_type', 50)->default('residential');
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('US');
            $table->string('postal_code', 20)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // KYC Documents
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('document_number', 100)->nullable();
            $table->string('file_path');
            $table->enum('verification_status', ['pending','verified','rejected','expired'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });

        // Account Types
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('category', ['savings','current','salary','loan','fd','rd','nre','nro']);
            $table->text('description')->nullable();
            $table->decimal('min_opening_balance', 12, 2)->default(0);
            $table->decimal('min_balance', 12, 2)->default(0);
            $table->decimal('interest_rate', 6, 4)->default(0);
            $table->enum('interest_calculation_method', ['daily_product','monthly','flat'])->default('daily_product');
            $table->enum('interest_posting_frequency', ['monthly','quarterly','annually'])->default('quarterly');
            $table->boolean('allow_cheque_book')->default(false);
            $table->boolean('allow_overdraft')->default(false);
            $table->decimal('overdraft_limit', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Accounts
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number', 20)->unique();
            $table->foreignId('account_type_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->string('currency_code', 3)->default('USD');
            $table->string('account_title');
            $table->enum('account_status', ['active','inactive','frozen','dormant','closed','blocked'])->default('active');
            $table->date('opening_date');
            $table->decimal('available_balance', 18, 2)->default(0);
            $table->decimal('ledger_balance', 18, 2)->default(0);
            $table->decimal('hold_amount', 18, 2)->default(0);
            $table->decimal('accrued_interest', 12, 2)->default(0);
            $table->decimal('interest_rate', 6, 4)->default(0);
            $table->decimal('minimum_balance', 12, 2)->default(0);
            $table->boolean('is_joint_account')->default(false);
            $table->date('last_transaction_date')->nullable();
            $table->date('dormancy_date')->nullable();
            $table->date('closed_date')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id','account_status']);
        });

        // Joint Account Holders
        Schema::create('joint_account_holders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained();
            $table->boolean('is_primary')->default(false);
            $table->string('authorization_level', 50)->default('either_or_survivor');
            $table->date('added_date')->nullable();
            $table->timestamps();
        });

        // Transactions
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_ref', 50)->unique();
            $table->string('transaction_type', 50);
            $table->string('transaction_mode', 50)->default('cash');
            $table->unsignedBigInteger('from_account_id')->nullable();
            $table->unsignedBigInteger('to_account_id')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->decimal('transaction_fee', 12, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 18, 2);
            $table->enum('transaction_status', ['pending','pending_approval','completed','failed','reversed','cancelled'])->default('pending');
            $table->text('description')->nullable();
            $table->text('narration')->nullable();
            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->date('value_date');
            $table->timestamp('posting_date')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->enum('approval_status', ['pending','approved','rejected'])->nullable();
            $table->unsignedBigInteger('reversal_of')->nullable();
            $table->boolean('is_reversed')->default(false);
            $table->string('channel', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info')->nullable();
            $table->timestamps();
            $table->index(['from_account_id','transaction_status']);
            $table->index(['to_account_id','transaction_status']);
            $table->index('value_date');
        });

        // Transaction Approvals
        Schema::create('transaction_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->integer('level')->default(1);
            $table->foreignId('approver_id')->constrained('users');
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // Loan Products
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 20)->unique();
            $table->string('product_name');
            $table->string('product_type', 50);
            $table->text('description')->nullable();
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->decimal('max_amount', 15, 2)->default(0);
            $table->integer('min_tenure_months')->default(1);
            $table->integer('max_tenure_months')->default(360);
            $table->decimal('base_interest_rate', 6, 4)->default(0);
            $table->enum('processing_fee_type', ['fixed','percentage'])->default('percentage');
            $table->decimal('processing_fee_value', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_collateral')->default(false);
            $table->boolean('prepayment_allowed')->default(true);
            $table->decimal('prepayment_penalty_rate', 6, 4)->default(0);
            $table->timestamps();
        });

        // Loans
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_account_number', 30)->unique();
            $table->foreignId('loan_product_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('sanctioned_amount', 15, 2)->nullable();
            $table->decimal('disbursed_amount', 15, 2)->default(0);
            $table->decimal('interest_rate', 6, 4);
            $table->enum('interest_type', ['fixed','floating','reducing_balance'])->default('reducing_balance');
            $table->integer('tenure_months');
            $table->decimal('emi_amount', 12, 2)->default(0);
            $table->date('application_date');
            $table->date('sanction_date')->nullable();
            $table->date('disbursement_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->decimal('outstanding_principal', 15, 2)->default(0);
            $table->decimal('outstanding_interest', 12, 2)->default(0);
            $table->decimal('total_outstanding', 15, 2)->default(0);
            $table->enum('loan_status', ['applied','under_review','approved','rejected','disbursed','active','closed','npa','written_off'])->default('applied');
            $table->string('npa_classification', 50)->nullable();
            $table->integer('overdue_days')->default(0);
            $table->decimal('penalty_amount', 12, 2)->default(0);
            $table->unsignedBigInteger('repayment_account_id')->nullable();
            $table->unsignedBigInteger('loan_officer_id')->nullable();
            $table->unsignedBigInteger('credit_manager_id')->nullable();
            $table->text('purpose')->nullable();
            $table->string('collateral_type', 100)->nullable();
            $table->decimal('collateral_value', 15, 2)->nullable();
            $table->integer('credit_score')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->decimal('prepayment_charges', 10, 2)->default(0);
            $table->decimal('foreclosure_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Loan Repayment Schedule
        Schema::create('loan_repayment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->integer('installment_number');
            $table->date('due_date');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('interest_amount', 12, 2);
            $table->decimal('penalty_amount', 12, 2)->default(0);
            $table->decimal('emi_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->enum('payment_status', ['pending','paid','partial','overdue'])->default('pending');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->timestamps();
        });

        // Loan Documents
        Schema::create('loan_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 100);
            $table->string('file_path');
            $table->string('file_name');
            $table->enum('verification_status', ['pending','verified','rejected'])->default('pending');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // Card Products
        Schema::create('card_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 20)->unique();
            $table->string('product_name');
            $table->string('card_type', 30);
            $table->string('card_network', 30);
            $table->decimal('annual_fee', 10, 2)->default(0);
            $table->decimal('joining_fee', 10, 2)->default(0);
            $table->decimal('reward_rate', 6, 4)->default(0);
            $table->decimal('cashback_rate', 6, 4)->default(0);
            $table->decimal('default_credit_limit', 12, 2)->default(0);
            $table->decimal('default_daily_limit', 12, 2)->default(0);
            $table->decimal('default_per_txn_limit', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Cards
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_number', 20)->unique();
            $table->foreignId('card_product_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('account_id')->constrained();
            $table->string('card_type', 30);
            $table->string('card_network', 30);
            $table->enum('card_status', ['inactive','active','blocked','expired','cancelled'])->default('inactive');
            $table->string('card_holder_name');
            $table->date('expiry_date');
            $table->date('issue_date');
            $table->date('activation_date')->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('available_limit', 12, 2)->default(0);
            $table->decimal('outstanding_amount', 12, 2)->default(0);
            $table->integer('billing_cycle_day')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('minimum_payment', 10, 2)->default(0);
            $table->integer('reward_points')->default(0);
            $table->boolean('international_enabled')->default(false);
            $table->boolean('online_enabled')->default(true);
            $table->boolean('contactless_enabled')->default(true);
            $table->decimal('daily_limit', 12, 2)->nullable();
            $table->decimal('per_transaction_limit', 12, 2)->nullable();
            $table->boolean('pin_change_required')->default(true);
            $table->string('blocked_reason')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Deposit Products
        Schema::create('deposit_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 20)->unique();
            $table->string('product_name');
            $table->string('product_type', 30)->default('fd');
            $table->text('description')->nullable();
            $table->decimal('min_amount', 12, 2)->default(0);
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->integer('min_tenure_days')->default(7);
            $table->integer('max_tenure_days')->default(3650);
            $table->decimal('interest_rate', 6, 4)->default(0);
            $table->decimal('premature_penalty_rate', 6, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('tds_applicable')->default(true);
            $table->timestamps();
        });

        // Fixed Deposits
        Schema::create('fixed_deposits', function (Blueprint $table) {
            $table->id();
            $table->string('fd_account_number', 30)->unique();
            $table->foreignId('deposit_product_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 6, 4);
            $table->integer('tenure_days')->default(0);
            $table->integer('tenure_months')->default(0);
            $table->decimal('maturity_amount', 15, 2)->default(0);
            $table->decimal('interest_amount', 12, 2)->default(0);
            $table->decimal('accrued_interest', 12, 2)->default(0);
            $table->date('start_date');
            $table->date('maturity_date');
            $table->enum('fd_status', ['active','matured','closed','prematurely_closed'])->default('active');
            $table->boolean('is_auto_renewal')->default(false);
            $table->string('renewal_instructions')->nullable();
            $table->date('premature_closure_date')->nullable();
            $table->decimal('premature_penalty', 10, 2)->default(0);
            $table->enum('payout_frequency', ['monthly','quarterly','on_maturity'])->default('on_maturity');
            $table->unsignedBigInteger('payout_account_id')->nullable();
            $table->boolean('tds_applicable')->default(true);
            $table->decimal('tds_rate', 6, 4)->default(10.00);
            $table->decimal('tds_deducted', 10, 2)->default(0);
            $table->timestamps();
        });

        // Standing Instructions
        Schema::create('standing_instructions', function (Blueprint $table) {
            $table->id();
            $table->string('instruction_number', 30)->unique();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('instruction_type', 50);
            $table->string('beneficiary_account_number', 30)->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('ifsc_code', 20)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('frequency', 30);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_execution_date')->nullable();
            $table->enum('status', ['active','paused','cancelled','completed'])->default('active');
            $table->text('description')->nullable();
            $table->integer('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();
        });

        // Cheque Books
        Schema::create('cheque_books', function (Blueprint $table) {
            $table->id();
            $table->string('cheque_book_number', 30)->unique();
            $table->foreignId('account_id')->constrained();
            $table->integer('number_of_leaves')->default(25);
            $table->string('start_cheque_number', 20);
            $table->string('end_cheque_number', 20);
            $table->date('issue_date');
            $table->enum('status', ['active','exhausted','cancelled'])->default('active');
            $table->timestamps();
        });

        // Cheques
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->string('cheque_number', 20);
            $table->foreignId('account_id')->constrained();
            $table->foreignId('cheque_book_id')->constrained();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('payee_name')->nullable();
            $table->date('cheque_date')->nullable();
            $table->date('presentation_date')->nullable();
            $table->date('clearance_date')->nullable();
            $table->enum('status', ['unused','issued','presented','cleared','bounced','stopped','cancelled'])->default('unused');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('bounce_reason')->nullable();
            $table->string('stop_reason')->nullable();
            $table->timestamps();
        });

        // Beneficiaries
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('beneficiary_name');
            $table->string('account_number', 30);
            $table->string('ifsc_code', 20)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('beneficiary_type', 50)->default('individual');
            $table->string('nickname', 100)->nullable();
            $table->enum('verification_status', ['pending','verified','failed'])->default('pending');
            $table->decimal('daily_limit', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action', 100);
            $table->string('module', 100);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('record_type')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('status', ['success','failure'])->default('success');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['module','action']);
            $table->index('created_at');
        });

        // AML Alerts
        Schema::create('aml_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_number', 30)->unique();
            $table->foreignId('customer_id')->constrained();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('alert_type', 100);
            $table->enum('severity', ['low','medium','high','critical'])->default('medium');
            $table->enum('alert_status', ['open','under_review','closed','escalated','false_positive'])->default('open');
            $table->string('rule_triggered', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount_involved', 15, 2)->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->boolean('str_filed')->default(false);
            $table->string('str_reference', 100)->nullable();
            $table->timestamps();
            $table->index(['customer_id','alert_status']);
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 100);
            $table->string('channel', 30)->default('in_app');
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id','is_read']);
        });

        // Customer Complaints
        Schema::create('customer_complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_number', 30)->unique();
            $table->foreignId('customer_id')->constrained();
            $table->string('category', 100);
            $table->string('subject');
            $table->text('description');
            $table->enum('priority', ['low','medium','high','critical'])->default('medium');
            $table->enum('status', ['open','in_progress','resolved','closed','escalated'])->default('open');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });

        // ATMs
        Schema::create('atms', function (Blueprint $table) {
            $table->id();
            $table->string('atm_id', 30)->unique();
            $table->string('atm_name');
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->string('location_type', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('status', ['active','inactive','maintenance','out_of_cash'])->default('active');
            $table->decimal('cash_available', 15, 2)->default(0);
            $table->timestamp('last_cash_replenishment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tables = [
            'atms','customer_complaints','notifications','aml_alerts','audit_logs',
            'beneficiaries','cheques','cheque_books','standing_instructions',
            'fixed_deposits','deposit_products','cards','card_products',
            'loan_documents','loan_repayment_schedules','loans','loan_products',
            'transaction_approvals','transactions',
            'joint_account_holders','accounts','account_types',
            'kyc_documents','customer_addresses','customers',
            'staff','branches','users',
        ];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
