<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('share_lineup', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sender_id')
                ->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignUuid('recepient_id')
                ->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignUuid('club_id')->nullable()->constrained('clubs')->nullOnDelete(); 
            $table->foreignUuid('match_id')->nullable()->constrained('matches')->nullOnDelete(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_lineup');
    }
};
