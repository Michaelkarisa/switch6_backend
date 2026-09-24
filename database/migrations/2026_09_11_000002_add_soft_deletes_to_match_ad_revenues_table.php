<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MatchAdRevenue extends BaseUuidModel, which includes the SoftDeletes
 * trait and therefore always queries with `deleted_at is null`. The table
 * was never given that column, so every query against it failed with
 * "Unknown column 'deleted_at'". This adds it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_ad_revenues', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('match_ad_revenues', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
