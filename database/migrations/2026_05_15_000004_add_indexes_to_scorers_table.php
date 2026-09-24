<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** GoalController / GoalService query by match_id and player_id. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scorers', function (Blueprint $table) {
            $table->index('match_id',  'idx_scorers_match_id');
            $table->index('player_id', 'idx_scorers_player_id');
        });
    }

    public function down(): void
    {
        Schema::table('scorers', function (Blueprint $table) {
            $table->dropIndex('idx_scorers_match_id');
            $table->dropIndex('idx_scorers_player_id');
        });
    }
};
