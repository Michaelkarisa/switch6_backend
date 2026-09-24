<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * share_lineup(s): the ShareLineup model has no explicit $table override,
 * so Eloquent expects the standard pluralized name "share_lineups" —
 * rename the table to match before altering it, and track import status.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::rename('share_lineup', 'share_lineups');

        Schema::table('share_lineups', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('match_id'); // pending | imported
            $table->timestamp('imported_at')->nullable()->after('status');
            $table->foreignUuid('imported_into_match_id')->nullable()->after('imported_at')
                ->constrained('matches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('share_lineups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('imported_into_match_id');
            $table->dropColumn(['status', 'imported_at']);
        });

        Schema::rename('share_lineups', 'share_lineup');
    }
};
