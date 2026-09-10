<?php

namespace App\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    public function __construct(
        private RateLimiter $limiter
    ) {}

    /**
     * Usage:
     * ->middleware('rate.limit:60,1')   // 60 requests / 1 minute
     * ->middleware('rate.limit:10,1')   // auth routes
     * ->middleware('rate.limit:120,1')  // read routes
     */
    public function handle(
        Request $request,
        Closure $next,
        int $maxAttempts = 60,
        int $decayMinutes = 1
    ): Response {
        $key = $this->resolveKey($request);
        $decaySeconds = $decayMinutes * 60;

        /*
         |--------------------------------------------------------------------------
         | Already locked out
         |--------------------------------------------------------------------------
         */
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->throttleResponse(
                $key,
                $maxAttempts
            );
        }

        /*
         |--------------------------------------------------------------------------
         | Record request
         |--------------------------------------------------------------------------
         */
        $this->limiter->hit(
            $key,
            $decaySeconds
        );

        /*
         |--------------------------------------------------------------------------
         | If threshold reached, create fixed cooldown window
         |--------------------------------------------------------------------------
         */
        if ($this->limiter->attempts($key) >= $maxAttempts) {

            // reset rolling window
            $this->limiter->clear($key);

            // recreate attempts with same expiry
            for ($i = 0; $i < $maxAttempts; $i++) {
                $this->limiter->hit(
                    $key,
                    $decaySeconds
                );
            }
        }

        $response = $next($request);

        return $this->appendHeaders(
            $response,
            $key,
            $maxAttempts
        );
    }

    /**
     * Per-user (or IP) + route key
     */
    private function resolveKey(
        Request $request
    ): string {

        $route =
            $request->route()?->getName()
            ?? $request->path();

        if ($request->is('v1/auth/login')) {

            $identity =
                $request->input('email', '')
                . '|'
                . $request->ip();

        } else {

            $identity =
                $request->user()?->id
                ?? $request->ip();
        }

        return 'rl:' . sha1(
            $identity . '|' . $route
        );
    }

    private function throttleResponse(
        string $key,
        int $maxAttempts
    ): JsonResponse {

        $retryAfter =
            $this->limiter->availableIn($key);

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please slow down.',
            'data' => null,
            'meta' => [
                'retry_after_seconds' => $retryAfter,
            ],
        ], 429, [

            'Retry-After' => $retryAfter,

            'X-RateLimit-Limit' =>
                $maxAttempts,

            'X-RateLimit-Remaining' =>
                0,

            'X-RateLimit-Reset' =>
                now()
                    ->addSeconds($retryAfter)
                    ->timestamp,
        ]);
    }

    private function appendHeaders(
        Response $response,
        string $key,
        int $maxAttempts
    ): Response {

        $remaining =
            max(
                0,
                $this->limiter->remaining(
                    $key,
                    $maxAttempts
                )
            );

        $response->headers->add([

            'X-RateLimit-Limit' =>
                $maxAttempts,

            'X-RateLimit-Remaining' =>
                $remaining,
        ]);

        return $response;
    }
}