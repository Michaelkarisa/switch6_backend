<?php

namespace App\Services;

use App\Models\League;
use Illuminate\Support\Facades\Cache;

class LeagueService
{
    private const CACHE_KEY = 'leagues:list';
    private const CACHE_TTL = 600;

    public function __construct(private AuditLogService $audit) {}

    public function list(?string $search = null)
    {
        if ($search === null) {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () =>
                League::query()->orderBy('leaguename')->get()
            );
        }

        return League::query()
            ->where(fn ($q) =>
                $q->where('leaguename', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
            )
            ->orderBy('leaguename')
            ->get();
    }

    public function create(array $data): League
    {
        $data['name'] = $data['name'] ?? $data['leaguename'];
        $league = League::create($data);
        $this->audit->log('created', 'leagues', 'League created', ['league_id' => $league->id], $league);
        Cache::forget(self::CACHE_KEY);
        Cache::delete('league_ids:' . md5($league->leaguename ?? ''));
        return $league;
    }

    public function findByIdentifier(string $identifier): ?League
    {
        return League::where('id', $identifier)
            ->orWhere('leaguename', $identifier)
            ->orWhere('name', $identifier)
            ->first();
    }
}
