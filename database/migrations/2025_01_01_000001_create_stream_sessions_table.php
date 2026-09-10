<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('match_id')
                ->nullable()
                ->constrained('matches')
                ->nullOnDelete();
            $table->foreignUuid('other_match_id')
                ->nullable()
                ->constrained('matches')
                ->nullOnDelete();
            $table->string('status')->default('live');
            $table->string('match_period')->nullable();
            $table->foreignUuid('broadcaster_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->json('members')->nullable();    
            $table->string('current_streamer')->nullable();
            $table->json('platform_targets')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_sessions');
    }
};
