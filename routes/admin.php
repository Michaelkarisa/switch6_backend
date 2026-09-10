<?php

use App\Http\Controllers\Api\V1\Admin\AdminAdvertisementController;
use App\Http\Controllers\Api\V1\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AdminClubController;
use App\Http\Controllers\Api\V1\Admin\AdminLeagueController;
use App\Http\Controllers\Api\V1\Admin\AdminMatchController;
use App\Http\Controllers\Api\V1\Admin\AdminNotificationController;
use App\Http\Controllers\Api\V1\Admin\AdminPlanController;
use App\Http\Controllers\Api\V1\Admin\AdminPlayerController;
use App\Http\Controllers\Api\V1\Admin\AdminRefereeController;
use App\Http\Controllers\Api\V1\Admin\AdminSystemController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;


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
            Route::get('matches',     [AdminAnalyticsController::class, 'matchAnalytics']);
            Route::get('ads',         [AdminAnalyticsController::class, 'adPerformance']);
            Route::get('devices',     [AdminAnalyticsController::class, 'deviceAnalytics']);
            Route::get('logs',        [AdminAnalyticsController::class, 'logAnalytics']);
 
        });

        // ── Users ──────────────────────────────────────────────────────────
        Route::prefix('users')->group(function () {
            Route::get    ('',                           [AdminUserController::class, 'index']);      // ?deleted=true for trashed
            Route::get    ('{user}',                     [AdminUserController::class, 'show']);
            Route::put    ('{user}',                     [AdminUserController::class, 'update']);
            Route::delete ('{user}',                     [AdminUserController::class, 'destroy']);    // soft delete
            Route::post   ('{id}/restore',               [AdminUserController::class, 'restore']);    // restore
            Route::delete ('{id}/force',                 [AdminUserController::class, 'forceDestroy']); // permanent
            Route::post   ('{user}/suspend',             [AdminUserController::class, 'suspend']);
            Route::post   ('{user}/activate',            [AdminUserController::class, 'activate']);
            Route::post   ('{user}/impersonate',         [AdminUserController::class, 'impersonate']);
            Route::get    ('{user}/login-history',       [AdminUserController::class, 'loginHistory']);
            Route::post   ('{user}/subscriptions/grant', [AdminPlanController::class, 'grant']);
        });

        // ── Plans ──────────────────────────────────────────────────────────
        Route::prefix('plans')->group(function () {
            Route::get    ('',              [AdminPlanController::class, 'index']);       // active plans
            Route::get    ('deleted',       [AdminPlanController::class, 'deleted']);     // trashed plans
            Route::post   ('',              [AdminPlanController::class, 'store']);
            Route::put    ('{plan}',        [AdminPlanController::class, 'update']);
            Route::post   ('{plan}/toggle', [AdminPlanController::class, 'toggle']);
            Route::delete ('{plan}',        [AdminPlanController::class, 'destroy']);    // soft delete
            Route::post   ('{id}/restore',  [AdminPlanController::class, 'restore']);    // restore
            Route::delete ('{id}/force',    [AdminPlanController::class, 'forceDestroy']); // permanent
        });

        // ── Subscriptions ──────────────────────────────────────────────────
        Route::prefix('subscriptions')->group(function () {
            Route::get    ('',                      [AdminPlanController::class, 'subscriptions']);
            Route::delete ('{subscription}/revoke', [AdminPlanController::class, 'revoke']);
            Route::post   ('{subscription}/extend', [AdminPlanController::class, 'extend']);
        });

        // ── Matches ────────────────────────────────────────────────────────
        Route::prefix('matches')->group(function () {
            Route::get    ('',                 [AdminMatchController::class, 'index']);       // ?deleted=true for trashed
            Route::delete ('{match}',          [AdminMatchController::class, 'destroy']);    // soft delete
            Route::post   ('{id}/restore',     [AdminMatchController::class, 'restore']);    // restore
            Route::delete ('{match}/force',    [AdminMatchController::class, 'forceDestroy']); // permanent
            Route::patch  ('{match}/reassign', [AdminMatchController::class, 'reassign']);
            
        });

        // ── Advertisements ─────────────────────────────────────────────────
        Route::prefix('advertisements')->group(function () {
            Route::get    ('',                          [AdminAdvertisementController::class, 'index']);    // ?deleted=true for trashed
            Route::put    ('{advertisement}',           [AdminAdvertisementController::class, 'update']);
            Route::patch  ('{advertisement}/status',    [AdminAdvertisementController::class, 'setStatus']);
            Route::delete ('{advertisement}',           [AdminAdvertisementController::class, 'destroy']); // soft delete
            Route::post   ('{id}/restore',              [AdminAdvertisementController::class, 'restore']); // restore
            Route::delete ('{id}/force',                [AdminAdvertisementController::class, 'forceDestroy']); // permanent
            Route::get    ('{advertisement}/analytics', [AdminAdvertisementController::class, 'analytics']);
        });

        // ── Clubs ──────────────────────────────────────────────────────────
        Route::prefix('clubs')->group(function () {
            Route::get    ('',             [AdminClubController::class, 'index']);        // ?deleted=true for trashed
            Route::post   ('',             [AdminClubController::class, 'store']);
            Route::put    ('{club}',       [AdminClubController::class, 'update']);
            Route::delete ('{club}',       [AdminClubController::class, 'destroy']);     // soft delete
            Route::post   ('{id}/restore', [AdminClubController::class, 'restore']);     // restore
            Route::delete ('{id}/force',   [AdminClubController::class, 'forceDestroy']); // permanent
        });

        // ── Players ────────────────────────────────────────────────────────
        Route::prefix('players')->group(function () {
            Route::get    ('',               [AdminPlayerController::class, 'index']);        // ?deleted=true, ?club_id=, ?position=
            Route::post   ('',               [AdminPlayerController::class, 'store']);
            Route::put    ('{player}',       [AdminPlayerController::class, 'update']);
            Route::delete ('{player}',       [AdminPlayerController::class, 'destroy']);     // soft delete
            Route::post   ('{id}/restore',   [AdminPlayerController::class, 'restore']);     // restore
            Route::delete ('{id}/force',     [AdminPlayerController::class, 'forceDestroy']); // permanent
        });

        // ── Referees ───────────────────────────────────────────────────────
        Route::prefix('referees')->group(function () {
            Route::get    ('',               [AdminRefereeController::class, 'index']);        // ?deleted=true for trashed
            Route::post   ('',               [AdminRefereeController::class, 'store']);
            Route::put    ('{referee}',      [AdminRefereeController::class, 'update']);
            Route::delete ('{referee}',      [AdminRefereeController::class, 'destroy']);     // soft delete
            Route::post   ('{id}/restore',   [AdminRefereeController::class, 'restore']);     // restore
            Route::delete ('{id}/force',     [AdminRefereeController::class, 'forceDestroy']); // permanent
        });

        // ── Leagues ────────────────────────────────────────────────────────
        Route::prefix('leagues')->group(function () {
            Route::get    ('',               [AdminLeagueController::class, 'index']);        // ?deleted=true for trashed
            Route::post   ('',               [AdminLeagueController::class, 'store']);
            Route::put    ('{league}',       [AdminLeagueController::class, 'update']);
            Route::delete ('{league}',       [AdminLeagueController::class, 'destroy']);     // soft delete (blocked if has matches)
            Route::post   ('{id}/restore',   [AdminLeagueController::class, 'restore']);     // restore
            Route::delete ('{id}/force',     [AdminLeagueController::class, 'forceDestroy']); // permanent
        });

        // ── Match Bids (slot auctions) ────────────────────────────────────
        Route::post('match-bids/resolve', [\App\Http\Controllers\Api\V1\MatchBidController::class, 'resolve']);

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
