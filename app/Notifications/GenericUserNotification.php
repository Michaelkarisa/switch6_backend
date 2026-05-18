<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class GenericUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private array $payload) {}

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
        return $this->payload['type'] ?? 'user.notification';
    }

    private function payload(): array
    {
        return array_merge([
            'type' => 'user.notification',
            'title' => 'Notification',
            'message' => '',
        ], $this->payload);
    }
}
