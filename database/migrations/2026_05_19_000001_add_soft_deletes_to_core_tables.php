<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds soft-delete support (deleted_at column) to the core business entities.
 *
 * Excluded (append-only / event tables — never soft-deleted):
 *   logs, logins, ad_events, ad_playback_events, scorers,
 *   lineups, match_views, comments, events, payments, transactions
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'clubs',
            'players',
            'referees',
            'leagues',
            'matches',
            'advertisements',
            'plans',
            'player_cards',
            'payments',
            'share_lineup',
            'transactions',
            'user_plan_subscriptions',
            'club_managers'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes(); // adds nullable deleted_at timestamp
            });
        }
    }

    public function down(): void
    {
       $tables = [
            'users',
            'clubs',
            'players',
            'referees',
            'leagues',
            'matches',
            'advertisements',
            'plans',
            'player_cards',
            'payments',
            'share_lineup',
            'transactions',
            'user_plan_subscriptions',
             'club_managers'
        ];


        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
