<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * match ad revenue: per-match, per-broadcaster payout ledger, computed
 * once bid slots for a period resolve.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('match_ad_revenues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignUuid('broadcaster_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('period')->nullable();
            $table->unsignedBigInteger('gross_amount')->default(0);
            $table->unsignedBigInteger('broadcaster_amount')->default(0);
            $table->unsignedBigInteger('platform_amount')->default(0);
            $table->decimal('share_percent', 5, 2)->default(50);
            $table->string('currency', 10)->default('KES');
            $table->timestamps();
            $table->unique(['match_id', 'period'], 'uniq_match_period_revenue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_ad_revenues');
    }
};
