<?php

use App\Http\Controllers\Api\V1\Admin\AdminAdvertisementController;
use App\Http\Controllers\Api\V1\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AdminMatchController;
use App\Http\Controllers\Api\V1\Admin\AdminNotificationController;
use App\Http\Controllers\Api\V1\Admin\AdminPlanController;
use App\Http\Controllers\Api\V1\Admin\AdminSystemController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
 * Admin API — all routes require:
 *   auth:sanctum    → valid Sanctum token
 *   active.user     → account status = active
 *   admin           → Spatie role check (admin OR superadmin)
 *   rate.limit:30,1 → 30 req/min per user
 *
 * Register this file in bootstrap/app.php:
 *   api: [__DIR__.'/../routes/api.php', __DIR__.'/../routes/admin.php']
 *
 * All endpoints are prefixed /api/v1/admin/…
 */

Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'active.user', 'admin', 'rate.limit:30,1'])
    ->group(function () {

        // ── Dashboard ──────────────────────────────────────────────────────
        Route::get('dashboard', [AdminAnalyticsController::class, 'dashboard']);

        // ── Analytics ──────────────────────────────────────────────────────
        Route::prefix('analytics')->group(function () {
            Route::get('audit-logs',  [AdminAnalyticsController::class, 'auditLogs']);
            Route::get('revenue',     [AdminAnalyticsController::class, 'revenue']);
            Route::get('user-growth', [AdminAnalyticsController::class, 'userGrowth']);
        });

        // ── Users ──────────────────────────────────────────────────────────
        Route::prefix('users')->group(function () {
            Route::get    ('',                           [AdminUserController::class, 'index']);
            Route::get    ('{user}',                     [AdminUserController::class, 'show']);
            Route::put    ('{user}',                     [AdminUserController::class, 'update']);
            Route::delete ('{user}',                     [AdminUserController::class, 'destroy']);
            Route::post   ('{user}/suspend',             [AdminUserController::class, 'suspend']);
            Route::post   ('{user}/activate',            [AdminUserController::class, 'activate']);
            Route::post   ('{user}/impersonate',         [AdminUserController::class, 'impersonate']);
            Route::get    ('{user}/login-history',       [AdminUserController::class, 'loginHistory']);
            Route::post   ('{user}/subscriptions/grant', [AdminPlanController::class, 'grant']);
        });

        // ── Plans ──────────────────────────────────────────────────────────
        Route::prefix('plans')->group(function () {
            Route::get    ('',              [AdminPlanController::class, 'index']);
            Route::post   ('',              [AdminPlanController::class, 'store']);
            Route::put    ('{plan}',        [AdminPlanController::class, 'update']);
            Route::post   ('{plan}/toggle', [AdminPlanController::class, 'toggle']);
            Route::delete ('{plan}',        [AdminPlanController::class, 'destroy']);
        });

        // ── Subscriptions ──────────────────────────────────────────────────
        Route::prefix('subscriptions')->group(function () {
            Route::get    ('',                      [AdminPlanController::class, 'subscriptions']);
            Route::delete ('{subscription}/revoke', [AdminPlanController::class, 'revoke']);
            Route::post   ('{subscription}/extend', [AdminPlanController::class, 'extend']);
        });

        // ── Matches ────────────────────────────────────────────────────────
        Route::prefix('matches')->group(function () {
            Route::get    ('',                 [AdminMatchController::class, 'index']);
            Route::delete ('{match}/force',    [AdminMatchController::class, 'forceDestroy']);
            Route::patch  ('{match}/reassign', [AdminMatchController::class, 'reassign']);
        });

        // ── Advertisements ─────────────────────────────────────────────────
        Route::prefix('advertisements')->group(function () {
            Route::get    ('',                          [AdminAdvertisementController::class, 'index']);
            Route::put    ('{advertisement}',           [AdminAdvertisementController::class, 'update']);
            Route::patch  ('{advertisement}/status',    [AdminAdvertisementController::class, 'setStatus']);
            Route::delete ('{advertisement}',           [AdminAdvertisementController::class, 'destroy']);
            Route::get    ('{advertisement}/analytics', [AdminAdvertisementController::class, 'analytics']);
        });

        // ── Payments ───────────────────────────────────────────────────────
        Route::prefix('payments')->group(function () {
            Route::get  ('',                  [AdminAdvertisementController::class, 'payments']);
            Route::get  ('revenue',           [AdminAdvertisementController::class, 'revenue']);
            Route::post ('{payment}/confirm', [AdminAdvertisementController::class, 'confirmPayment']);
            Route::post ('{payment}/refund',  [AdminAdvertisementController::class, 'refundPayment']);
        });

        // ── Notifications ──────────────────────────────────────────────────
        Route::post('notifications/broadcast', [AdminNotificationController::class, 'broadcast']);

        // ── System ─────────────────────────────────────────────────────────
        Route::prefix('system')->group(function () {
            Route::get    ('health',                 [AdminSystemController::class, 'health']);
            Route::post   ('cache/clear',            [AdminSystemController::class, 'clearCache']);
            Route::post   ('cache/clear-key',        [AdminSystemController::class, 'clearCacheKey']);
            Route::get    ('failed-jobs',            [AdminSystemController::class, 'failedJobs']);
            Route::post   ('failed-jobs/{id}/retry', [AdminSystemController::class, 'retryJob']);
            Route::delete ('failed-jobs/{id}',       [AdminSystemController::class, 'deleteJob']);
            Route::delete ('failed-jobs',            [AdminSystemController::class, 'flushFailedJobs']);
        });
    });
