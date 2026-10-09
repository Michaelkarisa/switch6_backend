<?php

namespace App\Services;

use App\Models\MatchViews;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MatchViewsService
{
    public function getForMatch(string $match_id): Collection
    {
        return MatchViews::where('match_id', $match_id)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Cumulative view total across all platforms: sum of the latest sample
     * per platform for this stream key.
     */
    public function totalForMatch(string $match_id): int
    {
        $latestPerPlatform = MatchViews::where('match_id', $match_id)
            ->select('platform', DB::raw('MAX(created_at) as latest'))
            ->groupBy('platform')
            ->get();

        $total = 0;

        foreach ($latestPerPlatform as $row) {
            $count = MatchViews::where('match_id', $match_id)
                ->where('platform', $row->platform)
                ->where('created_at', $row->latest)
                ->value('view_count');

            $total += (int) $count;
        }

        return $total;
    }

    public function create(array $data): MatchViews
    {
        return MatchViews::create([
            'match_id' => $data['match_id'] ?? null,
            'platform'   => $data['platform'] ?? null,
            'view_count' => $data['view_count'] ?? 0,
            'created_at' => now(),
        ]);
    }

    public function delete(string $id): int
    {
        return MatchViews::where('id', $id)->delete();
    }

    /**
     * Ingest a view-count sample pushed by the Rust media server's periodic
     * metrics tick (ApiClient::report_views -> POST /stream-views).
     * `stream_key` is the match id; `session_id`, if present, isn't
     * persisted — match_views has no stream_session_id column today.
     */
    public function createFromStream(array $data): MatchViews
    {
        return MatchViews::create([
            'match_id'   => $data['stream_key'],
            'platform'   => $data['platform'],
            'view_count' => $data['view_count'],
            'created_at' => now(),
        ]);
    }
}
