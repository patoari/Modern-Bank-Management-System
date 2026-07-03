<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Password Reset Tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Email Verification Tokens
        Schema::create('email_verification_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
        });

        // OTP Management for 2FA
        Schema::create('otp_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 6);
            $table->string('channel', 20)->default('email'); // email, sms
            $table->integer('attempts')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->index(['user_id', 'expires_at']);
        });

        // SMS Log for auditing
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('message');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('provider')->nullable();
            $table->string('external_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // Email Log for auditing
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to_email');
            $table->string('subject');
            $table->string('template');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // Beneficiary List
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('beneficiary_name');
            $table->string('account_number', 20);
            $table->string('ifsc_code', 20)->nullable();
            $table->string('bank_name')->nullable();
            $table->enum('account_type', ['savings', 'current'])->default('savings');
            $table->enum('relationship', ['self', 'family', 'business', 'other'])->default('family');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->string('verification_reference')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'account_number']);
        });

        // Standing Instructions
        Schema::create('standing_instructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_id')->constrained('accounts');
            $table->foreignId('to_account_id')->nullable()->constrained('accounts');
            $table->foreignId('beneficiary_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('instruction_name')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->enum('frequency', ['daily', 'weekly', 'fortnightly', 'monthly', 'quarterly', 'annually'])->default('monthly');
            $table->enum('debit_type', ['fixed', 'max', 'variable'])->default('fixed');
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'suspended', 'completed', 'cancelled'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamp('last_execution_at')->nullable();
            $table->timestamp('next_execution_at')->nullable();
            $table->unsignedBigInteger('executed_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['from_account_id', 'status']);
        });

        // Cheque Books
        Schema::create('cheque_books', function (Blueprint $table) {
            $table->id();
            $table->string('cheque_book_number', 50)->unique();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('from_cheque_no', 20);
            $table->string('to_cheque_no', 20);
            $table->enum('status', ['requested', 'issued', 'active', 'inactive', 'closed'])->default('requested');
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('delivery_mode')->default('counter');
            $table->string('delivery_address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Cheques
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->string('cheque_number', 20)->unique();
            $table->foreignId('cheque_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['available', 'issued', 'cleared', 'bounced', 'stopped', 'cancelled'])->default('available');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->string('payee_name')->nullable();
            $table->decimal('amount', 18, 2)->nullable();
            $table->text('stop_reason')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->unsignedBigInteger('stopped_by')->nullable();
            $table->string('bounce_reason')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['account_id', 'status']);
        });

        // Customer Complaints
        Schema::create('customer_complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_ref', 50)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('complaint_category');
            $table->enum('complaint_type', ['service_failure', 'incorrect_transaction', 'lost_card', 'documentation', 'other']);
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('description');
            $table->enum('status', ['open', 'acknowledged', 'under_investigation', 'resolved', 'closed', 'rejected'])->default('open');
            $table->text('resolution_notes')->nullable();
            $table->decimal('compensation_amount', 12, 2)->nullable();
            $table->enum('compensation_status', ['pending', 'approved', 'paid', 'rejected'])->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->date('registered_date');
            $table->date('target_resolution_date')->nullable();
            $table->date('resolved_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'status']);
        });

        // ATMs
        Schema::create('atms', function (Blueprint $table) {
            $table->id();
            $table->string('atm_id', 50)->unique();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->string('location_name');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('address');
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('postal_code', 20)->nullable();
            $table->string('landmark')->nullable();
            $table->enum('atm_type', ['standalone', 'branch_based', 'shared'])->default('standalone');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->time('opening_time')->default('00:00:00');
            $table->time('closing_time')->default('23:59:59');
            $table->boolean('operates_24_7')->default(true);
            $table->string('cash_available')->default('yes');
            $table->timestamp('last_cash_refill')->nullable();
            $table->string('installation_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('email_verification_tokens');
        Schema::dropIfExists('otp_tokens');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('beneficiaries');
        Schema::dropIfExists('standing_instructions');
        Schema::dropIfExists('cheque_books');
        Schema::dropIfExists('cheques');
        Schema::dropIfExists('customer_complaints');
        Schema::dropIfExists('atms');
    }
};
