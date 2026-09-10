<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public Transaction $transaction,
        public ?string $oldStatus,
        public string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    public function broadcastType(): string
    {
        return 'payment.status.updated';
    }

    private function payload(): array
    {
        return [
            'type' => 'payment.status.updated',
            'title' => 'Payment Status Updated',
            'message' => "Payment of {$this->paymentOf()} is {$this->newStatus}.",
            'payment_id' => $this->payment->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'payment_date' => optional($this->transaction->created_at)->toDateTimeString() ?? $this->transaction->created_at,
        ];
    }

    private function paymentOf(){
        if($this->payment->advertisement_id){
            return "advertisement";
        }else{
            return "plan";
        }
    }
}
