<?php

namespace App\Services;

use App\Models\StreamEvent;
use App\Models\StreamSession;
use Illuminate\Database\Eloquent\Collection;

class StreamEventService
{
    /**
     * @return Collection|null Null signals "session not found" to the caller.
     */

    public function getForMatch(string $match_id): ?Collection
    {
        $sessionId = StreamSession::where('match_id', $match_id)->value('id');

        if (!$sessionId) {
            return null;
        }

        return StreamEvent::where('session_id', $sessionId)
            ->orderBy('occurred_at')
            ->get();
    }

    public function create(array $data): StreamEvent
    {
        return StreamEvent::create([
            'match_id'    => $data['match_id'],
            'session_id'  => $data['session_id'],
            'type'        => $data['type'] ?? null,
            'payload'     => $data['payload'] ?? [],
            'occurred_at' => now(),
        ]);
    }

    public function delete(int $id): int
    {
        return StreamEvent::where('id', $id)->delete();
    }
}
