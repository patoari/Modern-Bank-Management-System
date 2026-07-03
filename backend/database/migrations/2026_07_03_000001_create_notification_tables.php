<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('event_key', 100)->unique();
            $table->enum('channel', ['email', 'sms', 'push'])->nullable();
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notification_type', 100);
            $table->enum('channel', ['email', 'sms', 'push', 'in_app']);
            $table->string('provider')->nullable();
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('response')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'notification_type', 'channel']);
            $table->index(['status']);
        });

        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_token')->unique();
            $table->string('device_name')->nullable();
            $table->string('platform', 50)->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type', 100);
            $table->string('channel', 30)->default('in_app');
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('push_tokens');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
    }
};
