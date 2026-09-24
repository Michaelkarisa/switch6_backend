<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->foreignUuid('club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('position', 50)->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->string('nationality')->nullable();
            $table->unsignedInteger('jersey_number')->nullable();
            $table->decimal('market_value', 14, 2)->nullable();
            $table->string('slug')->nullable();
            $table->string('role')->nullable();
            $table->string('profile_url')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
