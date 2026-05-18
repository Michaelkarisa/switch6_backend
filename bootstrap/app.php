<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as LocalLog;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health:   '/up',
        // api routes split into two files: main API + admin-only API
        then: function () {
            Route::middleware('api')->group(base_path('routes/api.php'));
            Route::middleware('api')->group(base_path('routes/admin.php'));
        },
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            // Auth / account guards
            'idempotency'        => \App\Middleware\IdempotencyMiddleware::class,
            'admin'              => \App\Middleware\EnsureAdmin::class,
            'active.user'        => \App\Middleware\EnsureUserIsActive::class,

            // Performance
            'rate.limit'         => \App\Middleware\RateLimitMiddleware::class,
            'cache.response'     => \App\Middleware\CacheResponseMiddleware::class,

            // Spatie permissions
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // ActivityLogger runs on every API request
        $middleware->api(append: [
            \App\Middleware\ActivityLogger::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e) {
            try {
                $request = app()->bound('request') ? app('request') : null;
                $status  = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

                LocalLog::error('Unhandled application exception', [
                    'message'   => $e->getMessage(),
                    'status'    => $status,
                    'exception' => $e::class,
                    'path'      => $request instanceof Request ? $request->path() : null,
                ]);

                if ($request instanceof Request) {
                    app(\App\Services\AuditLogService::class)->log(
                        action:      'exception.reported',
                        module:      $request->segment(2) ?? $request->segment(1) ?? 'general',
                        description: $e->getMessage(),
                        metadata: [
                            'level'           => 'error',
                            'status'          => 'failed',
                            'status_code'     => $status,
                            'exception_class' => $e::class,
                            'file'            => $e->getFile(),
                            'line'            => $e->getLine(),
                        ],
                        request: $request,
                    );
                }
            } catch (\Throwable $loggingError) {
                try {
                    LocalLog::error('Failed while recording exception audit log', [
                        'original_error' => $e->getMessage(),
                        'logging_error'  => $loggingError->getMessage(),
                    ]);
                } catch (\Throwable $ignored) {
                }
            }

            return false;
        });
    })
    ->create();
