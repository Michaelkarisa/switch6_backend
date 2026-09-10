<?php

namespace App\Services;

use App\Jobs\WriteAuditLogJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches audit writes to a background queue job so they never
 * block the HTTP response. Falls back to synchronous write on failure.
 */
class AuditLogService
{
    public function log(
        string   $action,
        ?string  $module      = null,
        ?string  $description = null,
        array    $metadata    = [],
        ?Model   $auditable   = null,
        ?Request $request     = null,
        ?string  $userId      = null,
    ): void {
        try {
            $request ??= request();
            $user     = $request?->user();

            $payload = [
                'user_id'      => $userId ?? $user?->id,
                'action'       => $action,
                'module'       => $module,
                'reference_id' => $auditable?->getKey(),
                'description'  => $description,
                'ip_address'   => $request?->ip(),
                'user_agent'   => $request?->userAgent(),
                'metadata'     => $metadata ?: null,
            ];

            WriteAuditLogJob::dispatch($payload)->onQueue('audit');

        } catch (\Throwable $e) {
            Log::warning('Audit queue dispatch failed, writing synchronously', [
                'action' => $action,
                'module' => $module,
                'error'  => $e->getMessage(),
            ]);

            $this->writeDirect($payload ?? []);
        }
    }

    private function writeDirect(array $payload): void
    {
        try {
            \App\Models\AuditLog::create($payload);
        } catch (\Throwable $e) {
            Log::error('Audit log write failed', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }
}
