<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * advertisements: campaign type (general vs bid), self-advertise support,
 * and a per-ad pricing snapshot.
 *
 * NOTE: user_plan_subscriptions.status keeps its DB-level default of
 * 'active' (changing a column default in-place needs doctrine/dbal, which
 * isn't guaranteed installed). PlanSubscriptionService now always sets
 * status explicitly to 'inactive' on creation, so the DB default is never
 * actually relied upon.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            // general | bid
            $table->string('campaign_type', 20)->default('general')->after('user_id');
            // set when a broadcaster is self-advertising / advertising their own match
            $table->foreignUuid('broadcaster_id')->nullable()->after('campaign_type')
                ->constrained('users')->nullOnDelete();
            $table->unsignedInteger('price')->nullable()->after('duration');
        });
    }

    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('broadcaster_id');
            $table->dropColumn(['campaign_type', 'price']);
        });
    }
};
