<?php

namespace App\Services;

use App\Models\Club;
use App\Models\League;
use App\Models\MatchModel;
use App\Models\User;
use Carbon\Carbon;
use Carbon\Traits\ToStringFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchService
{
    private const STATUSES = ['scheduled', 'live', 'completed', 'cancelled'];
    private const LEAGUE_CACHE_TTL = 300;

    public function __construct(
        private MatchFormatterService $formatter,
        private AuditLogService       $audit,
        private NotificationService   $notifications,
    ) {}

    /* ── Writes ────────────────────────────────────────────────── */

    public function create(array $data, ?string $authorId, ?Request $request = null): array
    {
        $this->validateMatchPayload($data);
       
        $match = DB::transaction(function () use ($data, $authorId, $request) {
            $match = MatchModel::create($this->buildPayload($data, $authorId));
            $match->load($this->relations());

            DB::afterCommit(fn () => $this->audit->log(
                'created', 'matches', 'Match created',
                ['match_id' => $match->id], $match, $request
            ));

            return $match;
        });
         $formatted = $this->formatter->format($match);
        DB::afterCommit(fn () => $this->notifications->notifyMatchCreated($formatted,$match));

        return ['match' => $match, 'formatted' => $formatted];
    }

    public function updateStatus(MatchModel $match, string $status, ?Request $request = null): MatchModel
    {
        $this->assertValidStatus($status);
        $oldStatus = $match->status;

        $match = DB::transaction(function () use ($match, $status, $oldStatus, $request) {
            $match->update(['status' => $status]);
            $match->load($this->relations());

            DB::afterCommit(fn () => $this->audit->log(
                'status_updated', 'matches', 'Match status updated',
                ['old_status' => $oldStatus, 'new_status' => $status], $match, $request
            ));

            return $match;
        });

        DB::afterCommit(fn () => $this->notifications->notifyMatchStatusUpdated($match, $oldStatus, $status));

        return $match;
    }

    public function update(MatchModel $match, array $data, ?Request $request = null): array
    {
        $this->validateMatchPayload(array_merge($match->toArray(), $data));

        $match = DB::transaction(function () use ($match, $data, $request) {
            $match->update([
                'match_date'     => $data['match_date']     ?? $match->match_date,
                'home_score'     => $data['home_score']     ?? $match->home_score,
                'away_score'     => $data['away_score']     ?? $match->away_score,
                'status'         => $data['status']         ?? $match->status,
                'venue'          => $data['venue']          ?? $match->venue,
                'referee'        => $data['referee']        ?? ($data['referee_id'] ?? $match->referee),
                'referee_id'     => array_key_exists('referee_id', $data) ? $data['referee_id'] : $match->referee_id,
                'home_formation' => $data['home_formation'] ?? $match->home_formation,
                'away_formation' => $data['away_formation'] ?? $match->away_formation,
                'home_club_id'   => $data['home_club_id']  ?? $match->home_club_id,
                'away_club_id'   => $data['away_club_id']  ?? $match->away_club_id,
                'league_id'      => array_key_exists('league_id', $data) ? $data['league_id'] : $match->league_id,
            ]);

            $match->load($this->relations());

            DB::afterCommit(fn () => $this->audit->log(
                'updated', 'matches', 'Match updated', ['match_id' => $match->id], $match, $request
            ));

            return $match;
        });

        return $this->formatter->format($match);
    }

    public function delete(MatchModel $match, ?Request $request = null): string
    {
        return DB::transaction(function () use ($match, $request) {
            $id = $match->id;

            DB::afterCommit(fn () => $this->audit->log(
                'deleted', 'matches', 'Match deleted', ['match_id' => $id], null, $request
            ));

            $match->delete();
            return $id;
        });
    }

    /* ── Reads ─────────────────────────────────────────────────── */

    public function listFormatted()
    {
        return MatchModel::with($this->relations())
            ->select($this->columns())
            ->orderBy('match_date')
            ->get()
            ->mapWithKeys(fn ($m) => [$m->id => $this->formatter->format($m)]);
    }

    public function paginatedFormatted(int $limit = 20, int $offset = 0)
    {
        return MatchModel::with($this->relations())
            ->select($this->columns())
            ->orderBy('match_date')
            ->skip($offset)->take($limit)
            ->get()
            ->mapWithKeys(fn ($m) => [$m->id => $this->formatter->format($m)]);
    }

    public function format(MatchModel $match): array
    {
        $match->loadMissing($this->relations());
        return $this->formatter->format($match);
    }

    public function byStatus(string $status)
    {
        $this->assertValidStatus($status);

        return MatchModel::with($this->relations())
            ->select($this->columns())
            ->where('status', $status)
            ->orderBy('match_date')
            ->get()
            ->mapWithKeys(fn ($m) => [$m->id => $this->formatter->format($m)]);
    }

    public function byLeague(string $leagueName)
    {
        $leagueIds = Cache::remember(
            'league_ids:' . md5($leagueName),
            self::LEAGUE_CACHE_TTL,
            fn () => League::query()
                ->where('name', 'like', "%{$leagueName}%")
                ->orWhere('short_name', 'like', "%{$leagueName}%")
                ->pluck('id')
        );

        return MatchModel::with($this->relations())
            ->select($this->columns())
            ->whereIn('league_id', $leagueIds)
            ->orderBy('match_date')
            ->get()
            ->mapWithKeys(fn ($m) => [$m->id => $this->formatter->format($m)]);
    }

    public function byAuthor(string $authorId)
    {
        return MatchModel::with($this->relations())
            ->select($this->columns())
            ->where('author_id', $authorId)
            ->orderByDesc('match_date')
            ->get()
            ->mapWithKeys(fn ($m) => [$m->id => $this->formatter->format($m)]);
    }

    /* ── Helpers ───────────────────────────────────────────────── */

    private function columns(): array
    {
        return [
            'id', 'league_id', 'home_club_id', 'away_club_id',
            'referee_id', 'author_id', 'match_date', 'home_score',
            'away_score', 'status', 'venue', 'home_formation', 'away_formation','slug'
        ];
    }

    private function relations(): array
    {
        return ['homeClub', 'awayClub', 'league', 'referee', 'author', 'views'];
    }

    private function buildPayload(array $data, ?string $authorId): array
    {
        $user = User::find($authorId);
        return [
            'match_date'     => $data['match_date'],
            'home_score'     => $data['home_score']     ?? 0,
            'away_score'     => $data['away_score']     ?? 0,
            'status'         => $data['status']         ?? 'scheduled',
            'venue'          => $data['venue']          ?? null,
            'referee'        => $data['referee']        ?? ($data['referee_id'] ?? null),
            'referee_id'     => $data['referee_id']     ?? null,
            'home_formation' => $data['home_formation'] ?? '4-4-2',
            'away_formation' => $data['away_formation'] ?? '4-4-2',
            'home_club_id'   => $data['home_club_id'],
            'away_club_id'   => $data['away_club_id'],
            'league_id'      => $data['league_id']      ?? ($data['leagueid'] ?? null),
            'author_id'      => $authorId?? $data['authorid']?? null,
            'match_rank'     => $this->matchRank($authorId??$data['authorid']),
            'type'           => $user->game_type,
            'slug'           => $this->slug($data),
        ];
    }

    private function slug(array $data):string{
    $homeClub = Club::find($data['home_club_id']);
    $awayClub = Club::find($data['away_club_id']);
    $time = Carbon::now()->toDateTimeString();
    $slug = "{$homeClub->name}&VS&{$awayClub->name}&{$time}";
        return $slug.str_replace(' ', '&',$slug,$slug);
    }
    private function matchRank(string $authorId):float{
       $user = User::find($authorId);
      return  $user->rank??0.0;
    }
    private function validateMatchPayload(array $data): void
    {
        if (empty($data['match_date']))  throw ValidationException::withMessages(['match_date'  => ['Match date is required.']]);
        if (empty($data['home_club_id'])) throw ValidationException::withMessages(['home_club_id' => ['Home club is required.']]);
        if (empty($data['away_club_id'])) throw ValidationException::withMessages(['away_club_id' => ['Away club is required.']]);
        if (($data['home_club_id'] ?? null) === ($data['away_club_id'] ?? null)) {
            throw ValidationException::withMessages(['away_club_id' => ['Home and away clubs cannot be the same.']]);
        }
        if (! empty($data['status'])) $this->assertValidStatus($data['status']);
    }

    private function assertValidStatus(string $status): void
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid status. Must be one of: ' . implode(', ', self::STATUSES)],
            ]);
        }
    }

   
}
