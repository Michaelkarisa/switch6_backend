<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * byStatus() → WHERE status; byLeague() → WHERE league_id (IN);
 * byAuthor() → WHERE author_id; listFormatted()/paginated() → ORDER BY match_date;
 * MatchFormatter eager-loads homeClub/awayClub → FK columns hit on every format.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->index('status',      'idx_matches_status');
            $table->index('league_id',   'idx_matches_league_id');
            $table->index('author_id',   'idx_matches_author_id');
            $table->index('match_date',  'idx_matches_match_date');
            $table->index('home_club_id','idx_matches_home_club_id');
            $table->index('away_club_id','idx_matches_away_club_id');
            $table->index('referee_id',  'idx_matches_referee_id');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('idx_matches_status');
            $table->dropIndex('idx_matches_league_id');
            $table->dropIndex('idx_matches_author_id');
            $table->dropIndex('idx_matches_match_date');
            $table->dropIndex('idx_matches_home_club_id');
            $table->dropIndex('idx_matches_away_club_id');
            $table->dropIndex('idx_matches_referee_id');
        });
    }
};
