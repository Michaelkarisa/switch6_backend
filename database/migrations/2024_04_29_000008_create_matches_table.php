<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('league_id')->nullable()->constrained('leagues')->nullOnDelete();
            $table->foreignUuid('home_club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
            $table->foreignUuid('away_club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
            $table->foreignUuid('referee_id')->nullable()->constrained('referees')->nullOnDelete();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->nullable();
            $table->timestamp('match_date')->nullable();
            $table->integer('home_score')->default(0);
            $table->integer('away_score')->default(0);
            $table->string('status')->default('scheduled');
            $table->string('venue')->nullable();
            $table->text('highlights')->nullable();
            $table->json('streamkey')->nullable();
            $table->string('home_formation')->nullable();
            $table->string('away_formation')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
