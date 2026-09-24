<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('match_bids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('match_id')->nullable()->constrained('matches')->nullOnDelete();
             $table->foreignUuid('advertisement_id')->nullable()->constrained('advertisements')->nullOnDelete();
             $table->string('slot')->nullable();
             $table->unsignedBigInteger('amaount')->default(0);
             $table->string('currency')->default('KES');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_bids');
    }
};
