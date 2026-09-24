<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('phone', 20)->nullable()->unique();
            $table->string('email', 100)->nullable()->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();

            // FIX: was missing — User model fillable + cast references these
            $table->boolean('isverified')->default(false);
            $table->timestamp('verification_date')->nullable();

            // FIX: was missing — EnsureUserIsActive middleware checks $user->status
            $table->string('status', 20)->default('active');
            $table->string('role', 50)->nullable();
            $table->float("rank")->nullable();
            $table->string('game_type')->nullable();
            $table->string('country')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        
        Schema::create('settings', function (Blueprint $table){
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean("match_notifications")->default(true);
            $table->boolean('lineup_auto_save')->default(true);
            $table->string('theme')->default('light');
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('settings');
    }
};
