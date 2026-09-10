<?php

namespace App\Notifications;

use App\Models\MatchModel;
use App\Services\MatchFormatterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class MatchCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $match, ) {}

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
        return 'match.created';
    }

    private function payload(): array
    {
        return [
            'type' => 'match.created',
            'title' => 'New Match Created',
            'message' => 'A new match has been scheduled.',
            'match_id' => $this->match['id'],
            'status' => $this->match['status'],
            'match_date' => optional($this->match['date'])->toDateTimeString() ?? $this->match['date'],
            'home_team' => $this->match['homeTeam.name'],
            'away_team' => $this->match['awayTeam.name'],
            'league_id' => $this->match['league.name'],
        ];
    }
}
