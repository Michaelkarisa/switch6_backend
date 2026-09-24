<?php

use App\Http\Controllers\Api\V1\AdEventController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\AdvertisementAnalyticsController;
use App\Http\Controllers\Api\V1\AdvertisementController;
use App\Http\Controllers\Api\V1\AdvertisementStreamController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClubController;
use App\Http\Controllers\Api\V1\DownloadController;
use App\Http\Controllers\Api\V1\GoalController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\LeagueController;
use App\Http\Controllers\Api\V1\LineupController;
use App\Http\Controllers\Api\V1\MatchAnalyticsController;
use App\Http\Controllers\Api\V1\MatchBidController;
use App\Http\Controllers\Api\V1\MatchController;
use App\Http\Controllers\Api\V1\MatchViewsController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentCallbackController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\PlayerController;
use App\Http\Controllers\Api\V1\RefereeController;
use App\Http\Controllers\Api\V1\RevenueController;
use App\Http\Controllers\Api\V1\BroadcasterRankController;
use App\Http\Controllers\Api\V1\ShareLineupController;
use App\Http\Controllers\Api\V1\StreamEventController;
use App\Http\Controllers\Api\V1\StreamSessionController;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// Auth (public — no token required)
// rate.limit:10,1  — brute-force protection
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('v1/auth')->middleware(['rate.limit:10,1'])->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Public read — near-realtime, NO cache, high rate limit
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('v1')->middleware(['rate.limit:120,1'])->group(function () {
    Route::get('matches',                         [MatchController::class, 'index']);
    Route::get('matches/paginated',               [MatchController::class, 'paginated']);
    Route::get('matches/live',                    [MatchController::class, 'live']);
    Route::get('matches/upcoming',                [MatchController::class, 'upcoming']);
    Route::get('matches/completed',               [MatchController::class, 'completed']);
    Route::get('matches/status/{status}',         [MatchController::class, 'byStatus']);
    Route::get('matches/league/{leagueName}',     [MatchController::class, 'byLeague']);
    Route::get('matches/{match}',                 [MatchController::class, 'show']);
    Route::get('mymatches/{author}',              [MatchController::class, 'byAuthor']);
    Route::get('lineups/by-match/{match}',        [LineupController::class, 'byMatch']);
    Route::get('ads/select',                      [AdvertisementController::class, 'select']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Reference data — cached, changes rarely
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('v1')->middleware(['rate.limit:60,1', 'cache.response:10'])->group(function () {
    Route::get('clubs',                           [ClubController::class, 'index']);
    Route::get('club/{identifier}',               [ClubController::class, 'showByIdentifier']);
    Route::get('leagues',                         [LeagueController::class, 'index']);
    Route::get('referees',                        [RefereeController::class, 'index']);
    Route::get('plans',                           [PlanController::class, 'index']);
    Route::get('plans/{plan}',                    [PlanController::class, 'show']);
});

// ─────────────────────────────────────────────────────────────────────────────
// System
// ─────────────────────────────────────────────────────────────────────────────
Route::get('v1/health',            [HealthController::class, 'index']);
Route::get('v1/download/{version?}', [DownloadController::class, 'downloadApk']);
Route::post('v1/payments/mpesa/callback', [PaymentCallbackController::class, 'mpesa']);

// ─────────────────────────────────────────────────────────────────────────────
// Authenticated — ALL roles (broadcaster, advertiser, admin)
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('v1')->middleware(['auth:sanctum', 'active.user', 'rate.limit:60,1'])->group(function () {
    // Auth management
    Route::post('auth/logout',           [AuthController::class, 'logout']);
    Route::post('auth/change-password',  [AuthController::class, 'changePassword']);
    // Notifications
    Route::get('notifications',                      [NotificationController::class, 'index']);
    Route::get('notifications/unread',               [NotificationController::class, 'unread']);
    Route::get('notifications/count',                [NotificationController::class, 'count']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/read-all',            [NotificationController::class, 'markAllRead']);
    Route::delete('notifications/{notification}',    [NotificationController::class, 'destroy']);
    // Plans & subscriptions
    Route::post('plans/{plan}/subscribe', [PlanController::class, 'subscribe'])->middleware('idempotency');
    Route::delete('subscriptions/{subscription}', [PlanController::class, 'cancel']);
    Route::get('subscriptions/current',           [PlanController::class, 'current']);
    Route::get('subscriptions/history',           [PlanController::class, 'history']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Broadcaster + Admin — match & lineup management
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('v1')->middleware(['auth:sanctum', 'active.user', 'rate.limit:30,1', 'idempotency'])->group(function () {
    // Matches
    Route::post  ('matches',                    [MatchController::class, 'store']);
    Route::put   ('matches/{match}',            [MatchController::class, 'update']);
    Route::get   ('matches/{match}/analytics',  [MatchAnalyticsController::class, 'show']);
    Route::post  ('matches/{match}/status',     [MatchController::class, 'updateStatus']);
    Route::delete('delete-matches/{match}',     [MatchController::class, 'destroy']);
    // Lineups
    Route::post  ('lineups',                    [LineupController::class, 'storeMany']);
    Route::delete('lineups/by-match/{match}',   [LineupController::class, 'destroyByMatch']);
    // Goals / scorers
    Route::post  ('matches/{match}/goals',      [GoalController::class, 'store']);
    Route::delete('goals/{scorer}',             [GoalController::class, 'destroy']);
    // Clubs / players / leagues / referees (broadcaster CRUD)
    Route::post  ('clubs',              [ClubController::class, 'store']);
    Route::delete('clubs/{club}',       [ClubController::class, 'destroy']);
    Route::post  ('players',            [PlayerController::class, 'store']);
    Route::post  ('players/batch',      [PlayerController::class, 'batchStore']);
    Route::delete('players/{player}',   [PlayerController::class, 'destroy']);
    Route::post  ('league',             [LeagueController::class, 'store']);
    Route::post  ('referee',            [RefereeController::class, 'store']);
    // Match views
    Route::post('match-views',          [MatchViewsController::class, 'store']);
    // Ad stream injection
    Route::post('ad-events',            [AdEventController::class, 'store']);
    Route::get('matches/{match}/ad-stream', [AdvertisementStreamController::class, 'forMatch']);
    // Share lineup
    Route::post  ('share-lineups',                    [ShareLineupController::class, 'store']);
    Route::get   ('share-lineups',                    [ShareLineupController::class, 'index']); // shared with me
    Route::get   ('share-lineups/sent',                [ShareLineupController::class, 'sent']);
    Route::get   ('share-lineups/{shareLineup}/preview', [ShareLineupController::class, 'preview']);
    Route::post  ('share-lineups/{shareLineup}/import', [ShareLineupController::class, 'import']);
    Route::delete('share-lineups/{shareLineup}',       [ShareLineupController::class, 'destroy']);
    Route::get   ('broadcasters/search',               [ShareLineupController::class, 'searchBroadcasters']);
    // Broadcaster revenue tab
    Route::get('revenue',                       [RevenueController::class, 'index']);
    Route::get('revenue/matches/{match}',       [RevenueController::class, 'forMatch']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Advertiser + Broadcaster + Admin — advertisement & payment management
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('v1')->middleware(['auth:sanctum', 'active.user', 'rate.limit:30,1'])->group(function () {
    // Advertisements (create, view, delete own)
    Route::get   ('ads',                           [AdvertisementController::class, 'index']);
    Route::post  ('ads',                           [AdvertisementController::class, 'store'])->middleware('idempotency');
    Route::get   ('ads/{advertisement}',           [AdvertisementController::class, 'show']);
    Route::delete('ads/{advertisement}',           [AdvertisementController::class, 'destroy']);
    Route::get   ('ads/{advertisement}/analytics', [AdvertisementAnalyticsController::class, 'show']);
    Route::get   ('ads/{advertisement}/payments',  [PaymentController::class, 'index']);
    // Match bids (bid tab)
    Route::get ('match-bids/matches',    [MatchBidController::class, 'matches']);
    Route::get ('match-bids/base-price', [MatchBidController::class, 'basePrice']);
    Route::get ('match-bids/status',     [MatchBidController::class, 'status']);
    Route::post('match-bids',            [MatchBidController::class, 'store'])->middleware('idempotency');
    Route::get ('match-bids/my',         [MatchBidController::class, 'my']);
    //  payments
    Route::post('payments',                    [PaymentController::class, 'store'])->middleware('idempotency');
    Route::post('payments/{payment}/confirm',  [PaymentController::class, 'confirm']);
    Route::post('payments/{payment}/fail',     [PaymentController::class, 'fail']);
    Route::get ('payments/{payment}/status',   [PaymentController::class, 'status']);
    Route::get ('payments/my',                 [PaymentController::class, 'myPayments']);
});

// NOTE: All admin routes (/api/v1/admin/…) are defined in routes/admin.php
// which is registered alongside this file in bootstrap/app.php.


/*
|--------------------------------------------------------------------------
| Stream backend API routes
|--------------------------------------------------------------------------
| Same URL surface as the original path-based route file, now dispatched
| through controllers/services instead of inline closures.
*/
Route::prefix('v1')->group(function () {
// ── stream_sessions ─────────────────────────────────────────────────────
Route::get('/stream-sessions',                 [StreamSessionController::class, 'index']);
Route::get('/stream-sessions/{match}',     [StreamSessionController::class, 'show']);
Route::post('/stream-sessions',                [StreamSessionController::class, 'store']);
Route::put('/stream-sessions/{match}',     [StreamSessionController::class, 'update']);
Route::delete('/stream-sessions/{match}',  [StreamSessionController::class, 'destroy']);
// ── stream_events ────────────────────────────────────────────────────────
Route::get('/stream-events/{match}',     [StreamEventController::class, 'index']);
Route::post('/stream-events',                [StreamEventController::class, 'store']);
Route::delete('/stream-events/{id}',         [StreamEventController::class, 'destroy']);
// ── broadcaster_rank ─────────────────────────────────────────────────────
Route::get('/broadcaster-rank/{broadcasterId}',               [BroadcasterRankController::class, 'show']);
Route::get('/broadcaster-rank/{broadcasterId}/highest-views', [BroadcasterRankController::class, 'highestViews']);
Route::post('/broadcaster-rank/{broadcasterId}/award',        [BroadcasterRankController::class, 'award']);
Route::put('/broadcaster-rank/{broadcasterId}',               [BroadcasterRankController::class, 'update']);
Route::delete('/broadcaster-rank/{broadcasterId}',            [BroadcasterRankController::class, 'destroy']);
});