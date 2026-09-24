<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('advertisement_id');
            $table->uuid('match_id')->nullable();
            $table->string('event_type', 30); // injected | played | completed
            $table->string('period')->nullable();
            $table->unsignedInteger('play_time')->nullable(); // seconds actually played
            $table->unsignedInteger('view_count')->nullable(); // optional live views snapshot
            $table->string('platform')->nullable(); // youtube | facebook | rtmp_custom
            $table->timestamps();
            $table->foreign('advertisement_id')
                ->references('id')
                ->on('advertisements')
                ->cascadeOnDelete();
            $table->foreign('match_id')
                ->references('id')
                ->on('matches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_events');
    }
};
