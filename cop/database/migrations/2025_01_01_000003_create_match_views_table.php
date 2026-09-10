<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
             $table->foreignUuid('match_id')
                ->nullable()
                ->constrained('matches')
                ->nullOnDelete();
            $table->string('platform')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamp('created_at');
        });


        Schema::table('match_views', function (Blueprint $table) {
            $table->index(['match_id', 'platform', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_views');
    }
};
