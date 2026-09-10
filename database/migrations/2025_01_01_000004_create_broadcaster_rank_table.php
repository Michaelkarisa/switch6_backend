<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcaster_rank', function (Blueprint $table) {
            $table->uuid('id')->primary();
             $table->foreignUuid('broadcaster_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->decimal('points', 12, 4)->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcaster_rank');
    }
};
