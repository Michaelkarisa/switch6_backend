<?php

namespace App\Services;

use App\Models\MatchModel;
use App\Models\StreamSession;
use Illuminate\Database\Eloquent\Collection;

class StreamSessionService
{
    public function all(): Collection
    {
        return StreamSession::all();
    }

    public function findByMatch(string $match_id): ?StreamSession
    {
        return StreamSession::where('match_id', $match_id)->first();
    }

    /**
     * `broadcaster_id` is not something the Rust media server can supply —
     * it has no broadcaster-identity model, only the match it's streaming
     * (see api/mod.rs's "streamKey is the match_id" note). The broadcaster
     * for a session is whoever authored the match, so we resolve it here
     * from `matches.author_id` rather than trusting a client-supplied value.
     */
    public function create(array $data): StreamSession
    {
        $broadcasterId = $data['broadcaster_id'] ?? null;

        if (!$broadcasterId && !empty($data['match_id'])) {
            $broadcasterId = MatchModel::where('id', $data['match_id'])->value('author_id');
        }

        return StreamSession::create([
            'match_id'            => $data['match_id'] ?? null,
            'status'              => $data['status'] ?? 'live',
            'match_period'        => $data['match_period'] ?? null,
            'broadcaster_id'      => $broadcasterId,
            'current_streamer'    => $data['current_streamer'] ?? null,
            'platform_targets'    => $data['platform_targets'] ?? [],
            'other_match_id'      => $data['other_match_id'] ?? null,
            'members'             => $data['members'] ?? [],
            'last_activity_at'    => now(),
        ]);
    }

    public function updateByMatch(string $match_id, array $data): int
    {
        $payload = array_filter([
            'status'               => $data['status'] ?? null,
            'current_streamer'  => $data['current_streamer'] ?? null,
            'platform_targets'     => array_key_exists('platform_targets', $data)
                ? $data['platform_targets']
                : null,
                 'members'     => array_key_exists('members', $data)
                ? $data['members']
                : null,
        ], fn ($v) => !is_null($v));

        $payload['last_activity_at'] = now();

        return StreamSession::where('match_id', $match_id)->update($payload);
    }

    public function deleteByMatch(string $match_id): int
    {
        return StreamSession::where('match_id', $match_id)->delete();
    }
}
