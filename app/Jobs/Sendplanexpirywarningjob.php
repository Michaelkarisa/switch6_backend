<?php

namespace App\Jobs;

use App\Mail\PlanExpiryWarningMail;
use App\Models\UserPlanSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPlanExpiryWarningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retry up to 3 times with exponential back-off.
     */
    public int $tries = 3;
    public int $backoff = 60; // seconds between retries

    public function __construct(
        public readonly UserPlanSubscription $subscription
    ) {}

    public function handle(): void
    {
        // Re-check the subscription is still active before sending
        $this->subscription->refresh();

        if (! $this->subscription->isActive()) {
            Log::info('PlanExpiryWarning: subscription no longer active, skipping.', [
                'subscription_id' => $this->subscription->id,
            ]);
            return;
        }

        // Double-check warning wasn't already sent by a concurrent job
        if ($this->subscription->expiry_warning_sent_at !== null) {
            return;
        }

        Mail::send(new PlanExpiryWarningMail($this->subscription));

        // Mark warning as sent so we don't re-queue it
        $this->subscription->update([
            'expiry_warning_sent_at' => now(),
        ]);

        Log::info('PlanExpiryWarning: email dispatched.', [
            'subscription_id' => $this->subscription->id,
            'user_id'         => $this->subscription->user_id,
            'expires_at'      => $this->subscription->expires_at,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('PlanExpiryWarningJob failed.', [
            'subscription_id' => $this->subscription->id,
            'error'           => $e->getMessage(),
        ]);
    }
}