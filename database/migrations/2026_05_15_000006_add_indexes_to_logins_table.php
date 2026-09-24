<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Auth/login history queries → WHERE user_id. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logins', function (Blueprint $table) {
            $table->index('user_id', 'idx_logins_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('logins', function (Blueprint $table) {
            $table->dropIndex('idx_logins_user_id');
        });
    }
};
