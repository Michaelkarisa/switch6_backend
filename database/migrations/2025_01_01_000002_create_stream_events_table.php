<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('match_id')
                 ->nullable();
            $table->foreignUuid('session_id')
                ->nullable()
                ->constrained('stream_sessions')
                ->nullOnDelete();
            $table->string('type')->nullable(); // goal/card/substitute/switch/ad/output add-remove, etc.
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
        });

        Schema::table('stream_events', function (Blueprint $table) {
            $table->index(['session_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_events');
    }
};
