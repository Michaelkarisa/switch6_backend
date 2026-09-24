<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lineups', function (Blueprint $table) {
             $table->uuid('id')->primary();
             $table->foreignUuid('match_id')->nullable()->constrained('matches')->cascadeOnDelete();
             $table->foreignUuid('player_id')->nullable()->constrained('players')->cascadeOnDelete();
             $table->foreignUuid('club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
             $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
             $table->string('position', 50)->nullable();
             $table->boolean('is_starter')->default(true);
             $table->integer('minute_in')->nullable();
             $table->integer('minute_out')->nullable();
             $table->timestamps();
             $table->unique(['match_id', 'player_id']); // ← add this
          });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineups');
    }
};
