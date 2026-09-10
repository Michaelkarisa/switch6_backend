<?php

namespace App\Services;

use App\Models\Club;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ClubService
{
    private const CACHE_KEY = 'clubs:list';
    private const CACHE_TTL = 600;

    public function __construct(private AuditLogService $audit) {}

    public function list(?string $search = null)
    {
        if ($search === null) {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () =>
                Club::with('players')->orderBy('name')->get()
            );
        }

        return Club::with('players')
            ->where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Club
    {
        $club = Club::create([...$data,'slug'=>$this->slug($data)]);
        $this->audit->log('created', 'clubs', 'Club created', ['club_id' => $club->id], $club);
        Cache::forget(self::CACHE_KEY);
        return $club;
    }

     private function slug(array $data):string{
      $slug = "{$data['name']}&{$data['city']}&{$data['founded_year']}";
        return $slug.str_replace(' ', '&',$slug,$slug);
    }
    public function findByIdentifier(string $identifier): ?Club
    {
        return Club::with('players')
            ->where('id', $identifier)
            ->orWhere('name', $identifier)
            ->first();
    }

    public function delete(Club $club): void
    {
        $id = $club->id;
        $club->delete();
        $this->audit->log('deleted', 'clubs', 'Club deleted', ['club_id' => $id]);
        Cache::forget(self::CACHE_KEY);
    }
}
