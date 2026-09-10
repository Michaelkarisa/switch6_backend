<?php

namespace App\Mail;

use App\Models\Advertisement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once an advertisement's payment is confirmed and the campaign
 * (general or bid) becomes active. Queued so it never blocks the payment
 * callback response.
 */
class AdslotPaymentEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Advertisement $advertisement) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->advertisement->user->email],
            subject: 'Your advertisement campaign is now active',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'adslotpurchaseemail',
            with: [
                'userName'     => $this->advertisement->user->name,
                'title'        => $this->advertisement->title,
                'campaignType' => $this->advertisement->campaign_type,
                'period'       => $this->advertisement->period,
                'endDate'      => optional($this->advertisement->end_date)->format('d M Y'),
            ],
        );
    }
}
