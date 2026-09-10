<?php

namespace App\Services;

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

    public function create(array $data): StreamSession
    {
        return StreamSession::create([
            'match_id'            => $data['match_id'] ?? null,
            'status'              => $data['status'] ?? 'live',
            'match_period'        => $data['match_period'] ?? null,
            'broadcaster_id'      => $data['broadcaster_id'] ?? null,
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
