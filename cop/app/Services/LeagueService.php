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
                League::query()->orderBy('name')->get()
            );
        }

        return League::query()
            ->where(fn ($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('short_name', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): League
    {
        $data['name'] = $data['name'] ?? $data['leaguename'];
        $data['slug'] = $this->slug($data);
        $league = League::create($data);
        $this->audit->log('created', 'leagues', 'League created', ['league_id' => $league->id], $league);
        Cache::forget(self::CACHE_KEY);
        Cache::delete('league_ids:' . md5($league->leaguename ?? ''));
        return $league;
    }
  private function slug(array $data):string{
      $slug = "{$data['name']}&{$data['city']}&{$data['founded_year']}";
        return $slug.str_replace(' ', '&',$slug,$slug);
    }
    public function findByIdentifier(string $identifier): ?League
    {
        return League::where('id', $identifier)
            ->orWhere('leaguename', $identifier)
            ->orWhere('name', $identifier)
            ->first();
    }
}
