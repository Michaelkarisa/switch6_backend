<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('slug')->nullable();
            $table->string('title');
            $table->string('file_type', 20); // image | video
            $table->string('file_path');
            $table->unsignedInteger('duration')->default(15);
            $table->string('period')->nullable(); // before_1st | halftime | after_2nd | etc
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // active | paused | expired
            $table->json('target_tags')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
