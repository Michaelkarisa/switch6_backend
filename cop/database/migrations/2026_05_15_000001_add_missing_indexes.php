<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds every missing index identified by auditing the live schema against
 * the query patterns in MatchService, LineupService, and the middleware.
 *
 * Run: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── matches ───────────────────────────────────────────────────────────
        // byStatus()  → WHERE status
        // byLeague()  → WHERE league_id (IN)
        // byAuthor()  → WHERE author_id
        // listFormatted() / paginated() → ORDER BY match_date
        // MatchFormatter eager-loads homeClub/awayClub → FK columns hit on every format
        Schema::table('matches', function (Blueprint $table) {
            $table->index('status',      'idx_matches_status');
            $table->index('league_id',   'idx_matches_league_id');
            $table->index('author_id',   'idx_matches_author_id');
            $table->index('match_date',  'idx_matches_match_date');
            $table->index('home_club_id','idx_matches_home_club_id');
            $table->index('away_club_id','idx_matches_away_club_id');
            $table->index('referee_id',  'idx_matches_referee_id');
        });

        // ── lineups ───────────────────────────────────────────────────────────
        // byMatch() / deleteByMatch() / storeMany() → WHERE match_id
        // match_id+player_id unique already exists; club_id used in eager-loads
        Schema::table('lineups', function (Blueprint $table) {
            $table->index('match_id', 'idx_lineups_match_id');
            $table->index('club_id',  'idx_lineups_club_id');
        });

        // ── players ───────────────────────────────────────────────────────────
        // ClubService::list() eager-loads players → WHERE club_id
        Schema::table('players', function (Blueprint $table) {
            $table->index('club_id', 'idx_players_club_id');
        });

        // ── scorers ───────────────────────────────────────────────────────────
        // GoalController / GoalService query by match_id and player_id
        Schema::table('scorers', function (Blueprint $table) {
            $table->index('match_id',  'idx_scorers_match_id');
            $table->index('player_id', 'idx_scorers_player_id');
        });

        // ── logs (audit_logs) ─────────────────────────────────────────────────
        // AuditLogService queries by user_id, module, and action
        // ActivityLogger filters by path — no index needed there (text column)
        Schema::table('logs', function (Blueprint $table) {
            $table->index('user_id',    'idx_logs_user_id');
            $table->index('module',     'idx_logs_module');
            $table->index('action',     'idx_logs_action');
            $table->index('created_at', 'idx_logs_created_at');
        });

        // ── logins ────────────────────────────────────────────────────────────
        // Auth/login history queries → WHERE user_id
        Schema::table('logins', function (Blueprint $table) {
            $table->index('user_id', 'idx_logins_user_id');
        });

        // ── ad_events ─────────────────────────────────────────────────────────
        // AdEventService → WHERE advertisement_id, WHERE match_id
        Schema::table('ad_events', function (Blueprint $table) {
            $table->index('advertisement_id', 'idx_ad_events_advertisement_id');
            $table->index('match_id',         'idx_ad_events_match_id');
        });

        // ── notifications ─────────────────────────────────────────────────────
        // NotificationService → WHERE read_at IS NULL (unread count queries)
        Schema::table('notifications', function (Blueprint $table) {
            $table->index('read_at', 'idx_notifications_read_at');
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

        Schema::table('lineups', function (Blueprint $table) {
            $table->dropIndex('idx_lineups_match_id');
            $table->dropIndex('idx_lineups_club_id');
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex('idx_players_club_id');
        });

        Schema::table('scorers', function (Blueprint $table) {
            $table->dropIndex('idx_scorers_match_id');
            $table->dropIndex('idx_scorers_player_id');
        });

        Schema::table('logs', function (Blueprint $table) {
            $table->dropIndex('idx_logs_user_id');
            $table->dropIndex('idx_logs_module');
            $table->dropIndex('idx_logs_action');
            $table->dropIndex('idx_logs_created_at');
        });

        Schema::table('logins', function (Blueprint $table) {
            $table->dropIndex('idx_logins_user_id');
        });

        Schema::table('ad_events', function (Blueprint $table) {
            $table->dropIndex('idx_ad_events_advertisement_id');
            $table->dropIndex('idx_ad_events_match_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_read_at');
        });
    }
};