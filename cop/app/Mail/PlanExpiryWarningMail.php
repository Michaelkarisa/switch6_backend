<?php

namespace App\Mail;
use App\Models\UserPlanSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanExpiryWarningMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly UserPlanSubscription $subscription
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to:     [ $this->subscription->user->email],
            subject: 'Your Switch6 plan expires in ' . $this->subscription->daysUntilExpiry() . ' days',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.plan-expiry-warning',
            with: [
                'userName'  => $this->subscription->user->name,
                'planName'  => $this->subscription->plan->name,
                'expiresAt' => $this->subscription->expires_at->format('d M Y'),
                'daysLeft'  => $this->subscription->daysUntilExpiry(),
                'renewUrl'  => config('app.frontend_url') . '/billing/renew?plan=' . $this->subscription->plan->slug,
            ],
        );
    }
}