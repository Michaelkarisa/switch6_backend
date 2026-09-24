<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** byMatch()/deleteByMatch()/storeMany() → WHERE match_id; club_id used in eager-loads. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lineups', function (Blueprint $table) {
            $table->index('match_id', 'idx_lineups_match_id');
            $table->index('club_id',  'idx_lineups_club_id');
        });
    }

    public function down(): void
    {
        Schema::table('lineups', function (Blueprint $table) {
            $table->dropIndex('idx_lineups_match_id');
            $table->dropIndex('idx_lineups_club_id');
        });
    }
};
