<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('player_cards', function (Blueprint $table) {
             $table->uuid('id')->primary();
             $table->foreignUuid('player_id')->nullable()->constrained('players')->cascadeOnDelete();
             $table->foreignUuid('league_id')->nullable()->constrained('leagues')->cascadeOnDelete();
             $table->foreignUuid('match_id')->nullable()->constrained('matches')->cascadeOnDelete();
             $table->unsignedSmallInteger('minute')->nullable();
             $table->string('card')->default("None");
             $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_cards');
    }
};
