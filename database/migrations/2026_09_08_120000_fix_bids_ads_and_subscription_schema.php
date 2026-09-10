<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── match_bids: fix "amaount" typo, add fields needed for the
        // before/halftime/fulltime slot-auction system ──────────────────
        Schema::table('match_bids', function (Blueprint $table) {
            $table->renameColumn('amaount', 'amount');
        });

        Schema::table('match_bids', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->after('advertisement_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignUuid('payment_id')->nullable()->after('user_id')
                ->constrained('payments')->nullOnDelete();
            // before_match | halftime | fulltime
            $table->string('period')->nullable()->after('payment_id');
            // 1-5, assigned once the auction for this match+period resolves
            $table->unsignedTinyInteger('slot_rank')->nullable()->after('period');
            // pending_payment | pending | won | lost | refunded
            $table->string('status', 20)->default('pending_payment')->after('slot_rank');
            $table->index(['match_id', 'period', 'status']);
        });

        // ── share_lineup: track whether a shared lineup has been imported ──
        Schema::table('share_lineup', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('match_id'); // pending | imported
            $table->timestamp('imported_at')->nullable()->after('status');
            $table->foreignUuid('imported_into_match_id')->nullable()->after('imported_at')
                ->constrained('matches')->nullOnDelete();
        });

        // ── advertisements: campaign type (general vs bid), self-advertise
        // support, and per-ad pricing snapshot ────────────────────────────
        Schema::table('advertisements', function (Blueprint $table) {
            // general | bid
            $table->string('campaign_type', 20)->default('general')->after('user_id');
            // set when a broadcaster is self-advertising / advertising their own match
            $table->foreignUuid('broadcaster_id')->nullable()->after('campaign_type')
                ->constrained('users')->nullOnDelete();
            $table->unsignedInteger('price')->nullable()->after('duration');
        });

        // NOTE: user_plan_subscriptions.status keeps its DB-level default of
        // 'active' (changing a column default in-place needs doctrine/dbal,
        // which isn't guaranteed installed). PlanSubscriptionService now
        // always sets status explicitly to 'inactive' on creation, so the
        // DB default is never actually relied upon.

        // ── match ad revenue: per-match, per-broadcaster payout ledger
        // computed once bid slots for a period resolve ────────────────────
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

        Schema::table('share_lineup', function (Blueprint $table) {
            $table->dropConstrainedForeignId('imported_into_match_id');
            $table->dropColumn(['status', 'imported_at']);
        });

        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('broadcaster_id');
            $table->dropColumn(['campaign_type', 'price']);
        });

        Schema::table('match_bids', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('payment_id');
            $table->dropColumn(['period', 'slot_rank', 'status']);
        });

        Schema::table('match_bids', function (Blueprint $table) {
            $table->renameColumn('amount', 'amaount');
        });
    }
};
