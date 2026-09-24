<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('currency', 10)->default('KES');
            $table->string('payment_method', 40)->default('mpesa');
            $table->string('phone', 20)->nullable();
             // subscription | advertisement   
            $table->string('type', 30)->default('subscription');
            // mpesa | card | bank_transfer
            $table->string('reference', 100)->nullable()->unique();
            $table->string('transaction_code', 100)->nullable()->unique();
            $table->string('status', 20)->default('pending');
            // pending | completed | failed | refunded
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();  
            $table->timestamps();
           // $table->index(['advertisement_id', 'status']);
            $table->index(['user_id', 'status']);
          //  $table->index(['subscription_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
