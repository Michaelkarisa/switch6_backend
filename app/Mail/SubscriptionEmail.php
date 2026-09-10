<?php

namespace App\Mail;

use App\Models\UserPlanSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once a subscription payment is confirmed and the subscription
 * becomes active. Queued so it never blocks the payment callback response.
 */
class SubscriptionEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly UserPlanSubscription $subscription) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->subscription->user->email],
            subject: "You're subscribed to the {$this->subscription->plan->name} plan",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'subscriptionemail',
            with: [
                'userName'  => $this->subscription->user->name,
                'planName'  => $this->subscription->plan->name,
                'quality'   => $this->subscription->quality,
                'startsAt'  => optional($this->subscription->starts_at)->format('d M Y'),
                'expiresAt' => optional($this->subscription->expires_at)->format('d M Y'),
            ],
        );
    }
}
