<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_plan_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained('plans')->restrictOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('quality')->default(480);
            // status: active | expired | cancelled | grace
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expiry_warning_sent_at')->nullable(); // track if 5-day email sent       // FK set after ad_payments table exists
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('expires_at');                   // used by the scheduler query
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_plan_subscriptions');
    }
};
