<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scorers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('match_id')->nullable()->constrained('matches')->cascadeOnDelete();
            $table->foreignUuid('player_id')->nullable()->constrained('players')->cascadeOnDelete();
            $table->foreignUuid('club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
            $table->integer('minute')->nullable();
            $table->string('goal_type')->default('regular');
            $table->foreignUuid('assist_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scorers');
    }
};
