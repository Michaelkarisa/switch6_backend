<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** AdEventService → WHERE advertisement_id, WHERE match_id. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_events', function (Blueprint $table) {
            $table->index('advertisement_id', 'idx_ad_events_advertisement_id');
            $table->index('match_id',         'idx_ad_events_match_id');
        });
    }

    public function down(): void
    {
        Schema::table('ad_events', function (Blueprint $table) {
            $table->dropIndex('idx_ad_events_advertisement_id');
            $table->dropIndex('idx_ad_events_match_id');
        });
    }
};
