<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * match_bids: fix "amaount" typo, add fields needed for the
 * before/halftime/fulltime slot-auction system.
 */
return new class extends Migration {
    public function up(): void
    {
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
    }

    public function down(): void
    {
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
