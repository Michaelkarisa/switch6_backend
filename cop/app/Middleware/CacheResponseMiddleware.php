<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache GET responses for slow-changing reference data only.
 *
 * Apply to: clubs, leagues, referees, plans
 * DO NOT apply to: matches, lineups, live scores (near-realtime)
 *
 * Usage:  ->middleware('cache.response')      // 10 min default
 *         ->middleware('cache.response:60')   // 60 min
 */
class CacheResponseMiddleware
{
    public function handle(Request $request, Closure $next, int $ttlMinutes = 10): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        if ($request->header('Cache-Control') === 'no-cache') {
            return $next($request);
        }

        $key = $this->resolveKey($request);

        if (Cache::has($key)) {
            $cached = Cache::get($key);

            return response()->json($cached['body'], $cached['status'])
                ->withHeaders(array_merge($cached['headers'], ['X-Cache' => 'HIT']));
        }

        $response = $next($request);

        if ($this->isCacheable($response)) {
            /** @var JsonResponse $response */
            Cache::put($key, [
                'body'    => $response->getData(true),
                'status'  => $response->getStatusCode(),
                'headers' => $this->safeHeaders($response),
            ], now()->addMinutes($ttlMinutes));

            $response->headers->set('X-Cache', 'MISS');
        }

        return $response;
    }

    private function resolveKey(Request $request): string
    {
        $identity = $request->user()?->id ?? 'guest';

        return 'rc:' . sha1($identity . '|' . $request->fullUrl());
    }

    private function isCacheable(Response $response): bool
    {
        return $response instanceof JsonResponse
            && $response->getStatusCode() >= 200
            && $response->getStatusCode() < 300;
    }

    private function safeHeaders(Response $response): array
    {
        $carry = [];
        foreach (['Content-Type', 'X-RateLimit-Limit', 'X-RateLimit-Remaining'] as $h) {
            if ($response->headers->has($h)) {
                $carry[$h] = $response->headers->get($h);
            }
        }

        return $carry;
    }

    public static function bust(string $tag): void
    {
        Cache::forget('rc:' . $tag);
    }
}
