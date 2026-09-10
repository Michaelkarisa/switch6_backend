<?php

namespace App\Console\Commands;

use App\Jobs\SendPlanExpiryWarningJob;
use App\Models\UserPlanSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckPlanExpiry extends Command
{
    protected $signature   = 'plans:check-expiry
                                {--days=5  : Days before expiry to send the warning}
                                {--dry-run : List subscriptions without dispatching jobs}';

    protected $description = 'Queue expiry-warning emails for subscriptions expiring within N days, '
                           . 'and mark truly expired subscriptions as expired.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dry  = $this->option('dry-run');

        $this->info("Running plan expiry check (window: {$days} days)" . ($dry ? ' [DRY RUN]' : ''));

        // ── 1. Send expiry-warning emails ─────────────────────────────────
        $expiringSoon = UserPlanSubscription::with(['user', 'plan'])
            ->expiringSoon($days)
            ->get();

        $this->line("Found {$expiringSoon->count()} subscription(s) expiring within {$days} days.");

        foreach ($expiringSoon as $sub) {
            if ($dry) {
                $this->line("  [DRY] Would email: {$sub->user->email} | Plan: {$sub->plan->name} | Expires: {$sub->expires_at}");
                continue;
            }

            // Dispatch to the default queue
            SendPlanExpiryWarningJob::dispatch($sub)->onQueue('emails');

            $this->line("  ✓ Queued warning for: {$sub->user->email} ({$sub->daysUntilExpiry()} days left)");

            Log::info('plans:check-expiry – warning queued', [
                'subscription_id' => $sub->id,
                'user_id'         => $sub->user_id,
                'expires_at'      => $sub->expires_at,
            ]);
        }

        // ── 2. Auto-expire overdue subscriptions ──────────────────────────
        $expiredCount = 0;

        if (! $dry) {
            $expiredCount = UserPlanSubscription::expired()
                ->update(['status' => 'expired']);
        } else {
            $expiredCount = UserPlanSubscription::expired()->count();
            $this->line("[DRY] Would mark {$expiredCount} subscription(s) as expired.");
        }

        if (! $dry && $expiredCount > 0) {
            $this->line("Marked {$expiredCount} subscription(s) as expired.");
            Log::info("plans:check-expiry – marked {$expiredCount} subscriptions expired.");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}