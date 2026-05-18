<?php

namespace App\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as LocalLog;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogger
{
    // Route parameter names to probe when resolving a reference ID
    private const ROUTE_ID_PARAMS = [
        'id', 'user', 'match', 'club', 'player',
        'referee', 'league', 'lineup', 'notification',
    ];

    // Sensitive fields never included in body summaries
    private const EXCLUDED_FIELDS = [
        'password',
        'old_password',
        'new_password',
        'password_confirmation',
        'token',
        'current_password',
        'national_id',
        'date_of_birth',
        'phone',
        'cv_url',
        'attachment_url',
        'id_number',
        'bank_account',
        'email',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningInConsole()) {
            return $next($request);
        }

        $start    = microtime(true);
        $response = $next($request);

        try {
            if (! $this->shouldLog($request, $response)) {
                return $response;
            }

            $duration   = round((microtime(true) - $start) * 1000, 2);
            $statusCode = $response->getStatusCode();
            $status     = $statusCode >= 400 ? 'failed' : 'success';
            $level      = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'info');
            $userId     = optional($request->user())->id;

            // ── DB audit log ──────────────────────────────────────────
            try {
                app(\App\Services\AuditLogService::class)->log(
                    action: strtolower($request->method()) . '_request',
                    module: $this->resolveModule($request),
                    description: $request->method() . ' ' . $request->path() . ' — ' . $statusCode,
                    metadata: [
                        'level'        => $level,
                        'status'       => $status,
                        'status_code'  => $statusCode,
                        'duration_ms'  => $duration,
                        'reference_id' => $this->resolveReferenceId($request),
                        'query'        => $request->query(),
                        'body'         => $this->safeBodySummary($request),
                    ],
                    request: $request,
                    userId: $userId
                );
            } catch (\Throwable $e) {
                LocalLog::warning('Database audit log skipped', [
                    'path'   => $request->path(),
                    'method' => $request->method(),
                    'error'  => $e->getMessage(),
                ]);
            }

            // ── Local log — only for server errors (5xx) ──────────────
            // Info/warning activity is already captured in the audit table above;
            // logging it again here would just create noise.
            if ($statusCode >= 500) {
                try {
                    LocalLog::error('Server error on HTTP request', [
                        'method'      => $request->method(),
                        'path'        => $request->path(),
                        'status'      => $statusCode,
                        'duration_ms' => $duration,
                        'user_id'     => $userId,
                    ]);
                } catch (\Throwable $ignored) {
                    // never break the request because local logging failed
                }
            }

        } catch (\Throwable $e) {
            try {
                LocalLog::warning('Activity logging skipped', [
                    'path'   => $request->path(),
                    'method' => $request->method(),
                    'error'  => $e->getMessage(),
                ]);
            } catch (\Throwable $ignored) {
            }
        }

        return $response;
    }

    /**
     * Derive the module name from the URL, skipping the api/vN prefix.
     *
     * /api/v1/matches/123  → matches
     * /api/v1/auth/login   → auth
     * /health              → health
     */
    protected function resolveModule(Request $request): string
    {
        $segments = $request->segments(); // ['api', 'v1', 'matches', '123']

        // Drop leading 'api' segment
        if (isset($segments[0]) && $segments[0] === 'api') {
            array_shift($segments);
        }

        // Drop version segment (v1, v2, …)
        if (isset($segments[0]) && preg_match('/^v\d+$/i', $segments[0])) {
            array_shift($segments);
        }

        return $segments[0] ?? 'general';
    }

    /**
     * Resolve a reference ID from known route parameters.
     */
    protected function resolveReferenceId(Request $request): ?string
    {
        try {
            foreach (self::ROUTE_ID_PARAMS as $param) {
                $raw = $request->route($param);

                if ($raw === null) {
                    continue;
                }

                return $raw instanceof Model
                    ? (string) $raw->getKey()
                    : (string) $raw;
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Return only the field names submitted (not values),
     * with sensitive fields stripped out.
     */
    protected function safeBodySummary(Request $request): array
    {
        $submitted = array_keys($request->except(self::EXCLUDED_FIELDS));

        return [
            'submitted_fields' => $submitted,
            'field_count'      => count($submitted),
        ];
    }

    protected function shouldLog(Request $request, Response $response): bool
    {
        if ($request->is('up') || $request->is('_ignition/*')) {
            return false;
        }

        if ($request->is('api/logs') || $request->is('api/logs/*')) {
            return false;
        }

        return true;
    }
}