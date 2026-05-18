<?php

namespace App\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    public function __construct(private RateLimiter $limiter) {}

    /**
     * Usage:  ->middleware('rate.limit:60,1')   // 60 req / 1 min
     *         ->middleware('rate.limit:10,1')    // auth routes
     *         ->middleware('rate.limit:120,1')   // read routes
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveKey($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->throttleResponse($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->appendHeaders($response, $key, $maxAttempts);
    }

    /** Key is per-user (or IP for guests) scoped to route — prevents global bleed */
    private function resolveKey(Request $request): string
    {
        $identity = $request->user()?->id ?? $request->ip();
        $route    = $request->route()?->getName() ?? $request->path();

        return 'rl:' . sha1($identity . '|' . $route);
    }

    private function throttleResponse(string $key, int $maxAttempts): JsonResponse
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please slow down.',
            'data'    => null,
            'meta'    => ['retry_after_seconds' => $retryAfter],
        ], 429, [
            'Retry-After'           => $retryAfter,
            'X-RateLimit-Limit'     => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    private function appendHeaders(Response $response, string $key, int $maxAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit'     => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $this->limiter->remaining($key, $maxAttempts)),
        ]);

        return $response;
    }
}
