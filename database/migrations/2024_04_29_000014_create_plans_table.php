<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 60);                    // e.g. Starter, Pro, Enterprise
            $table->string('slug', 60)->unique();          // starter | pro | enterprise
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);  // price in KES (smallest unit)
            $table->string('currency', 10)->default('KES');
            $table->unsignedSmallInteger('duration_days')->default(30); // billing cycle
            $table->unsignedSmallInteger('max_matches')->nullable();    // null = unlimited
            $table->unsignedSmallInteger('max_cameras')->nullable();
            $table->json('quality')->nullable();
            $table->boolean('ads_enabled')->default(false);
            $table->boolean('analytics_enabled')->default(false);
            $table->boolean('most_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable();          // extra feature flags as JSON
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
