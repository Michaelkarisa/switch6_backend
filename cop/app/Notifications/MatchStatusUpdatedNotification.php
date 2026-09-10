<?php

namespace App\Notifications;

use App\Models\MatchModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class MatchStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MatchModel $match,
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
        return 'match.status.updated';
    }

    private function payload(): array
    {
        return [
            'type' => 'match.status.updated',
            'title' => 'Match Status Updated',
            'message' => "Match is now {$this->newStatus}.",
            'match_id' => $this->match->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'match_date' => optional($this->match->match_date)->toDateTimeString() ?? $this->match->match_date,
        ];
    }
}
