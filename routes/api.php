<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClubController;
use App\Http\Controllers\Api\V1\PlayerController;
use App\Http\Controllers\Api\V1\LeagueController;
use App\Http\Controllers\Api\V1\RefereeController;
use App\Http\Controllers\Api\V1\MatchController;
use App\Http\Controllers\Api\V1\LineupController;
use App\Http\Controllers\Api\V1\MatchViewController;
use App\Http\Controllers\Api\V1\GoalController;
use App\Http\Controllers\Api\V1\DownloadController;
use App\Http\Controllers\Api\V1\HealthController;

use App\Http\Controllers\Api\V1\AdvertisementController;
use App\Http\Controllers\Api\V1\AdvertisementAnalyticsController;
use App\Http\Controllers\Api\V1\AdvertisementStreamController;
use App\Http\Controllers\Api\V1\AdEventController;

use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\AdPaymentController;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTH (PUBLIC - RATE LIMITED)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')
        ->middleware(['rate.limit:10,1'])
        ->group(function () {

            Route::post('login', [AuthController::class, 'login']);

            Route::post('register', [AuthController::class, 'register'])
                ->middleware('idempotency');
        });

    /*
    |--------------------------------------------------------------------------
    | PUBLIC READ (CACHED + RATE LIMITED)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['rate.limit:120,1', 'cache.response:10'])->group(function () {

        // MATCHES
        Route::get('matches', [MatchController::class, 'index']);
        Route::get('matches/{match}', [MatchController::class, 'show']);
        Route::get('matches/status/{status}', [MatchController::class, 'byStatus']);
        Route::get('matches/league/{leagueName}', [MatchController::class, 'byLeague']);
        Route::get('matches/live', [MatchController::class, 'live']);
        Route::get('matches/upcoming', [MatchController::class, 'upcoming']);
        Route::get('matches/completed', [MatchController::class, 'completed']);
        Route::get('mymatches/{author}', [MatchController::class, 'byAuthor']);

        // REFERENCE DATA
        Route::get('clubs', [ClubController::class, 'index']);
        Route::get('club/{identifier}', [ClubController::class, 'showByIdentifier']);

        Route::get('leagues', [LeagueController::class, 'index']);
        Route::get('leagues/{identifier}', [LeagueController::class, 'showByIdentifier']);

        Route::get('referees', [RefereeController::class, 'index']);

        Route::get('plans', [PlanController::class, 'index']);
        Route::get('plans/{plan}', [PlanController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | LIVE / RUNTIME OPERATIONS (RATE LIMITED ONLY)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['rate.limit:120,1'])->group(function () {

        Route::post('matches/{match}/status', [MatchController::class, 'updateStatus']);
        Route::post('matches/{match}/add-goal', [GoalController::class, 'store']);
    });

    /*
    |--------------------------------------------------------------------------
    | ADS RUNTIME PIPELINE
    |--------------------------------------------------------------------------
    */
    Route::prefix('ads')
        ->middleware(['rate.limit:120,1'])
        ->group(function () {

            Route::post('select', [AdvertisementController::class, 'select']);
            Route::post('inject', [AdvertisementStreamController::class, 'inject']);

            Route::post('events', [AdEventController::class, 'store'])
                ->middleware('idempotency');
        });

    /*
    |--------------------------------------------------------------------------
    | PROTECTED USER AREA
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:api', 'active.user'])->group(function () {

        /*
        | USER AUTH ACTIONS
        */
        Route::post('auth/change-password', [AuthController::class, 'changePassword'])
            ->middleware('idempotency');

        Route::post('auth/logout', [AuthController::class, 'logout'])
            ->middleware('idempotency');

        /*
        | ADMIN ONLY
        */
        Route::middleware('role:admin')->group(function () {
            Route::delete('users/{user}', [AuthController::class, 'destroyUser']);
            Route::get('health', [HealthController::class, 'health']);
            Route::get('database/status', [HealthController::class, 'databaseStatus']);
            Route::get('download/{filename}', [DownloadController::class, 'download']);
        });

        /*
        | WRITE OPERATIONS (IDEMPOTENT + RATE LIMITED)
        */
        Route::middleware(['rate.limit:30,1', 'idempotency'])->group(function () {

            // CLUBS
            Route::post('clubs', [ClubController::class, 'store']);
            Route::post('clubs/batch', [ClubController::class, 'batchStore']);
            Route::delete('clubs/{club}', [ClubController::class, 'destroy']);

            // PLAYERS
            Route::post('players', [PlayerController::class, 'store']);
            Route::post('players/batch', [PlayerController::class, 'batchStore']);
            Route::put('players/{player}', [PlayerController::class, 'update']);
            Route::delete('players/{player}', [PlayerController::class, 'destroy']);

            // REFEREES
            Route::post('referee', [RefereeController::class, 'store']);
            Route::delete('referees/{referee}', [RefereeController::class, 'destroy']);

            // LEAGUES
            Route::post('league', [LeagueController::class, 'store']);

            // MATCHES
            Route::post('matches', [MatchController::class, 'store']);
            Route::post('matches/batch', [MatchController::class, 'batchStore']);
            Route::put('matches/{match}', [MatchController::class, 'update']);
            Route::delete('matches/{match}', [MatchController::class, 'destroy']);

            // LINEUPS
            Route::post('lineups', [LineupController::class, 'storeMany']);
            Route::delete('lineups/by-match/{match}', [LineupController::class, 'destroyByMatch']);
            Route::delete('lineups/{lineup}', [LineupController::class, 'destroy']);

            // ANALYTICS
            Route::post('views', [MatchViewController::class, 'store']);
        });

        /*
        |--------------------------------------------------------------------------
        | ADS MANAGEMENT + PAYMENTS
        |--------------------------------------------------------------------------
        */
        Route::prefix('ads')->group(function () {

            Route::middleware(['rate.limit:60,1'])->group(function () {

                Route::post('/', [AdvertisementController::class, 'store'])
                    ->middleware('idempotency');

                Route::get('/', [AdvertisementController::class, 'index']);

                Route::get('{advertisement}', [AdvertisementController::class, 'show']);

                Route::delete('{advertisement}', [AdvertisementController::class, 'destroy']);

                Route::get('{advertisement}/analytics', [AdvertisementAnalyticsController::class, 'show']);

                Route::get('{advertisement}/payments', [AdPaymentController::class, 'index']);

                Route::post('{advertisement}/payments', [AdPaymentController::class, 'store'])
                    ->middleware('idempotency');
            });

            Route::post('payments/{payment}/confirm', [AdPaymentController::class, 'confirm'])
                ->middleware(['rate.limit:60,1', 'idempotency']);

            Route::post('payments/{payment}/fail', [AdPaymentController::class, 'fail'])
                ->middleware('rate.limit:60,1');

            Route::get('payments/my', [AdPaymentController::class, 'myPayments'])
                ->middleware('rate.limit:60,1');
        });

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTIONS
        |--------------------------------------------------------------------------
        */
        Route::middleware(['rate.limit:30,1'])->group(function () {

            Route::post('plans/{plan}/subscribe', [PlanController::class, 'subscribe']);
            Route::delete('subscriptions/{subscription}', [PlanController::class, 'cancel']);
            Route::get('subscriptions/current', [PlanController::class, 'current']);
            Route::get('subscriptions/history', [PlanController::class, 'history']);
        });
    });
});