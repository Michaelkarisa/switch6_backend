<?php

namespace App\Services;

use App\Models\Lineup;
use App\Models\MatchModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LineupService
{
    public function __construct(private AuditLogService $audit) {}

    /**
     * Upsert all lineups for a match in 2 DB round-trips regardless of batch size.
     * 1. One SELECT to load existing rows keyed by player_id
     * 2. One bulk upsert (INSERT … ON DUPLICATE KEY UPDATE)
     */
    public function storeMany(array $items): array
    {
        return DB::transaction(function () use ($items) {
            if (empty($items)) {
                return [];
            }

            $items = array_map(function (array $data) {
                $data['is_starter'] = $data['is_starter'] ?? true;
                return $data;
            }, $items);

            $matchId  = $items[0]['match_id'];
            $existing = Lineup::where('match_id', $matchId)
                ->get()
                ->keyBy('player_id');

            $now      = now();
            $toUpsert = [];
            $created  = [];
            $updated  = [];

            foreach ($items as $data) {
                $current = $existing->get($data['player_id']);

                if ($current) {
                    $dirty = collect($data)->filter(
                        fn ($value, $key) => isset($current->$key) && (string) $current->$key !== (string) $value
                    )->keys()->all();

                    if (! empty($dirty)) {
                        $updated[] = ['lineup_id' => $current->id, 'changes' => $dirty];
                    }

                    $toUpsert[] = array_merge($data, [
                        'id'         => $current->id,
                        'updated_at' => $now,
                        'created_at' => $current->created_at,
                    ]);
                } else {
                    $created[]  = ['match_id' => $data['match_id'], 'player_id' => $data['player_id']];
                    $toUpsert[] = array_merge($data, [
                        'id'         => (string) Str::uuid(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            Lineup::upsert(
                $toUpsert,
                ['id'],
                ['club_id', 'position', 'is_starter', 'minute_in', 'minute_out', 'updated_at']
            );

            DB::afterCommit(function () use ($created, $updated, $items) {
                if (! empty($created)) {
                    $this->audit->log('created', 'lineups', 'Lineups created', ['count' => count($created)]);
                }
                if (! empty($updated)) {
                    $this->audit->log('updated', 'lineups', 'Lineups updated', ['changes' => $updated]);
                }
                $this->audit->log('synced', 'lineups', 'Lineups synced', ['count' => count($items)]);
            });

            return Lineup::where('match_id', $matchId)
                ->whereIn('player_id', array_column($items, 'player_id'))
                ->with(['player', 'club'])
                ->get()
                ->all();
        });
    }

    public function byMatch(MatchModel $match)
    {
        return Lineup::with(['player', 'club'])
            ->where('match_id', $match->id)
            ->get();
    }

    public function deleteByMatch(MatchModel $match): int
    {
        $deleted = Lineup::where('match_id', $match->id)->delete();

        if ($deleted > 0) {
            DB::afterCommit(fn () => $this->audit->log(
                'deleted_many', 'lineups', 'Match lineups deleted',
                ['match_id' => $match->id, 'deleted' => $deleted],
                $match
            ));
        }

        return $deleted;
    }
}
