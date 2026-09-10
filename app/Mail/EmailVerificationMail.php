<?php

namespace App\Mail;
use App\Models\User;
use App\Models\UserPlanSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to:     [ $this->user->email],
            subject: 'Your verification code is '. $this->code ,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification',
            with: [
                'userName'  => $this->user->name,
                'code'      => $this->code,
                'pageUrl'   => config('app.frontend_url') . '/email-verification=' . $this->code,
            ],
        );
    }
}