<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
|
| All recurring background tasks for this application are registered here.
| Run `php artisan schedule:list` to see the full schedule.
| Make sure your server cron runs: * * * * * php artisan schedule:run
|
*/

// ── Plan expiry ───────────────────────────────────────────────────────────
// Queue warning emails for subscriptions expiring within 5 days, and
// mark truly expired subscriptions as expired.
Schedule::command('plans:check-expiry --days=5')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Scheduled plans:check-expiry failed.');
    });

// ── Advertisement expiry ──────────────────────────────────────────────────
// Auto-expire advertisements whose end_date has passed.
Schedule::command('ads:expire')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Scheduled ads:expire failed.');
    });

// ── Bid slot auctions ────────────────────────────────────────────────────
// Resolve bid auctions whose 1-hour bidding window has closed (applies to
// before_match, halftime, and fulltime alike).
Schedule::command('bids:resolve-expired')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Scheduled bids:resolve-expired failed.');
    });

// ── Ad events pruning ─────────────────────────────────────────────────────
// Remove AdEvent records older than 90 days to keep the table lean.
// Runs weekly on Sunday at 02:00 to avoid peak hours.
Schedule::command('ads:prune-events --days=90')
    ->weeklyOn(0, '02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Scheduled ads:prune-events failed.');
    });