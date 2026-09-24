<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AuditLogService queries by user_id, module, and action.
 * ActivityLogger filters by path — no index needed there (text column).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->index('user_id',    'idx_logs_user_id');
            $table->index('module',     'idx_logs_module');
            $table->index('action',     'idx_logs_action');
            $table->index('created_at', 'idx_logs_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropIndex('idx_logs_user_id');
            $table->dropIndex('idx_logs_module');
            $table->dropIndex('idx_logs_action');
            $table->dropIndex('idx_logs_created_at');
        });
    }
};
