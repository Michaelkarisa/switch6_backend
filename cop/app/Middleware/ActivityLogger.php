<?php

namespace App\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as LocalLog;
use Symfony\Component\HttpFoundation\Response;

/**
 * ActivityLogger — production-grade HTTP audit middleware.
 *
 * Enhancements over original:
 *  - Parses User-Agent into device_type / browser / os
 *  - Records response_size in bytes
 *  - Records memory_peak_mb
 *  - Records request_id (UUID per request, useful for distributed tracing)
 *  - Records referrer
 *  - Throttles bot traffic: logged but flagged, not silenced
 *  - Sensitive routes (health, ignition, jobs, horizon) are skipped
 */
class ActivityLogger
{
    // Route parameter names to probe when resolving a reference ID
    private const ROUTE_ID_PARAMS = [
        'id', 'user', 'match', 'club', 'player',
        'referee', 'league', 'lineup', 'notification',
        'advertisement', 'plan',
    ];

    // Sensitive fields never included in body summaries
    private const EXCLUDED_FIELDS = [
        'password', 'old_password', 'new_password', 'password_confirmation',
        'token', 'current_password', 'national_id', 'date_of_birth', 'phone',
        'cv_url', 'attachment_url', 'id_number', 'bank_account', 'email',
        'card_number', 'cvv', 'secret', 'api_key',
    ];

    // Paths skipped entirely (no audit row written)
    private const SKIP_PATHS = [
        'up', '_ignition/*', 'api/logs', 'api/logs/*',
        'horizon/*', 'telescope/*', 'api/health',
        'favicon.ico', '*.map',
    ];

    // ──────────────────────────────────────────────────────────────

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningInConsole()) {
            return $next($request);
        }

        $start     = microtime(true);
        $memBefore = memory_get_usage(true);
        $response  = $next($request);

        try {
            if (! $this->shouldLog($request, $response)) {
                return $response;
            }

            $durationMs   = round((microtime(true) - $start) * 1000, 2);
            $memPeakMb    = round((memory_get_peak_usage(true) - $memBefore) / 1024 / 1024, 2);
            $statusCode   = $response->getStatusCode();
            $status       = $statusCode >= 400 ? 'failed' : 'success';
            $level        = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'info');
            $userId       = optional($request->user())->id;
            $ua           = (string) $request->userAgent();

            // Parse UA into human-readable dimensions
            $deviceInfo   = $this->parseUserAgent($ua);

            // Response byte size (best-effort; streaming responses may be 0)
            $responseSize = is_string($response->getContent())
                ? strlen($response->getContent())
                : 0;

            // ── DB audit log ──────────────────────────────────────────
            try {
                app(\App\Services\AuditLogService::class)->log(
                    action:      strtolower($request->method()) . '_request',
                    module:      $this->resolveModule($request),
                    description: $request->method() . ' ' . $request->path() . ' — ' . $statusCode,
                    metadata: [
                        // Core
                        'level'         => $level,
                        'status'        => $status,
                        'status_code'   => $statusCode,
                        'duration_ms'   => $durationMs,
                        'memory_peak_mb'=> $memPeakMb,
                        'reference_id'  => $this->resolveReferenceId($request),
                        'query'         => $request->query(),
                        'body'          => $this->safeBodySummary($request),
                        // Response
                        'response_size_bytes' => $responseSize,
                        // Client / device
                        'device_type'   => $deviceInfo['device_type'],
                        'browser'       => $deviceInfo['browser'],
                        'browser_ver'   => $deviceInfo['browser_ver'],
                        'os'            => $deviceInfo['os'],
                        'is_bot'        => $deviceInfo['is_bot'],
                        'referrer'      => $request->headers->get('referer'),
                        'content_type'  => $request->header('Content-Type'),
                        'accept_lang'   => substr((string) $request->header('Accept-Language', ''), 0, 30),
                        // Tracing
                        'request_id'    => $request->header('X-Request-ID') ?? $this->makeRequestId(),
                    ],
                    request: $request,
                    userId:  $userId
                );
            } catch (\Throwable $e) {
                LocalLog::warning('Database audit log skipped', [
                    'path'   => $request->path(),
                    'method' => $request->method(),
                    'error'  => $e->getMessage(),
                ]);
            }

            // ── Local log — only server errors (5xx) ──────────────────
            if ($statusCode >= 500) {
                try {
                    LocalLog::error('Server error on HTTP request', [
                        'method'      => $request->method(),
                        'path'        => $request->path(),
                        'status'      => $statusCode,
                        'duration_ms' => $durationMs,
                        'user_id'     => $userId,
                    ]);
                } catch (\Throwable $ignored) {}
            }

        } catch (\Throwable $e) {
            try {
                LocalLog::warning('Activity logging skipped', [
                    'path'   => $request->path(),
                    'method' => $request->method(),
                    'error'  => $e->getMessage(),
                ]);
            } catch (\Throwable $ignored) {}
        }

        return $response;
    }

    // ──────────────────────────────────────────────────────────────
    // User-Agent parsing
    // ──────────────────────────────────────────────────────────────

    /**
     * Returns device_type / browser / browser_ver / os / is_bot
     * without any third-party library.
     */
    protected function parseUserAgent(string $ua): array
    {
        if ($ua === '') {
            return $this->unknownDevice();
        }

        // ── Bot detection ─────────────────────────────────────────
        if (preg_match(
            '/bot|crawl|spider|slurp|bingbot|googlebot|facebookexternalhit|whatsapp|
             semrush|ahrefs|mj12|dotbot|sogou|yandex|duckduck|archive\.org/ix',
            $ua
        )) {
            return [
                'device_type' => 'bot',
                'browser'     => 'Bot',
                'browser_ver' => null,
                'os'          => null,
                'is_bot'      => true,
            ];
        }

        // ── Device type ───────────────────────────────────────────
        if (preg_match('/tablet|ipad/i', $ua)) {
            $deviceType = 'tablet';
        } elseif (preg_match(
            '/mobile|android(?!.*tablet)|iphone|ipod|blackberry|opera mini|windows phone/i',
            $ua
        )) {
            $deviceType = 'mobile';
        } else {
            $deviceType = 'desktop';
        }

        // ── Browser + version ─────────────────────────────────────
        $browser    = 'Other';
        $browserVer = null;

        if (preg_match('/Edg\/([0-9.]+)/i', $ua, $m)) {
            $browser = 'Edge'; $browserVer = $m[1];
        } elseif (preg_match('/OPR\/([0-9.]+)|Opera\/([0-9.]+)/i', $ua, $m)) {
            $browser = 'Opera'; $browserVer = $m[1] ?: $m[2];
        } elseif (preg_match('/Chrome\/([0-9.]+)/i', $ua, $m)) {
            $browser = 'Chrome'; $browserVer = $m[1];
        } elseif (preg_match('/Firefox\/([0-9.]+)/i', $ua, $m)) {
            $browser = 'Firefox'; $browserVer = $m[1];
        } elseif (preg_match('/Version\/([0-9.]+).*Safari/i', $ua, $m)) {
            $browser = 'Safari'; $browserVer = $m[1];
        } elseif (preg_match('/MSIE ([0-9.]+)|Trident\/.*rv:([0-9.]+)/i', $ua, $m)) {
            $browser = 'IE'; $browserVer = $m[1] ?: $m[2];
        }

        // Trim to major.minor only
        if ($browserVer) {
            $parts      = explode('.', $browserVer);
            $browserVer = implode('.', array_slice($parts, 0, 2));
        }

        // ── OS ────────────────────────────────────────────────────
        $os = 'Other';
        if (preg_match('/Windows NT 10\.0/i', $ua))        $os = 'Windows 10/11';
        elseif (preg_match('/Windows NT 6\.3/i', $ua))     $os = 'Windows 8.1';
        elseif (preg_match('/Windows/i', $ua))             $os = 'Windows';
        elseif (preg_match('/iPhone OS ([0-9_]+)/i', $ua, $m)) {
            $os = 'iOS ' . str_replace('_', '.', $m[1]);
        } elseif (preg_match('/iPad/i', $ua))              $os = 'iPadOS';
        elseif (preg_match('/Android ([0-9.]+)/i', $ua, $m)) {
            $os = 'Android ' . $m[1];
        } elseif (preg_match('/Mac OS X ([0-9_]+)/i', $ua, $m)) {
            $os = 'macOS ' . str_replace('_', '.', $m[1]);
        } elseif (preg_match('/CrOS/i', $ua))              $os = 'ChromeOS';
        elseif (preg_match('/Linux/i', $ua))               $os = 'Linux';

        return [
            'device_type' => $deviceType,
            'browser'     => $browser,
            'browser_ver' => $browserVer,
            'os'          => $os,
            'is_bot'      => false,
        ];
    }

    private function unknownDevice(): array
    {
        return ['device_type' => 'unknown', 'browser' => null, 'browser_ver' => null, 'os' => null, 'is_bot' => false];
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers (carried over + minor improvements)
    // ──────────────────────────────────────────────────────────────

    protected function resolveModule(Request $request): string
    {
        $segments = $request->segments();

        if (isset($segments[0]) && $segments[0] === 'api') {
            array_shift($segments);
        }
        if (isset($segments[0]) && preg_match('/^v\d+$/i', $segments[0])) {
            array_shift($segments);
        }

        return $segments[0] ?? 'general';
    }

    protected function resolveReferenceId(Request $request): ?string
    {
        try {
            foreach (self::ROUTE_ID_PARAMS as $param) {
                $raw = $request->route($param);
                if ($raw === null) continue;
                return $raw instanceof Model ? (string) $raw->getKey() : (string) $raw;
            }
            return null;
        } catch (\Throwable) {
            return null;
        }
    }

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
        foreach (self::SKIP_PATHS as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }
        return true;
    }

    private function makeRequestId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}