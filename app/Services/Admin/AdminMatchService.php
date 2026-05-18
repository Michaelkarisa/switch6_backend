<?php

namespace App\Services\Admin;

use App\Models\MatchModel;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class AdminMatchService
{
    public function __construct(private AuditLogService $audit) {}

    public function paginate(Request $request): LengthAwarePaginator
    {
        return MatchModel::with(['homeClub', 'awayClub', 'league', 'author'])
            ->select([
                'id', 'league_id', 'home_club_id', 'away_club_id',
                'author_id', 'match_date', 'home_score', 'away_score',
                'status', 'venue', 'home_formation', 'away_formation', 'created_at',
            ])
            ->when($request->query('status'),    fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('author_id'), fn ($q, $v) => $q->where('author_id', $v))
            ->when($request->query('league_id'), fn ($q, $v) => $q->where('league_id', $v))
            ->when($request->query('from'),      fn ($q, $v) => $q->whereDate('match_date', '>=', $v))
            ->when($request->query('to'),        fn ($q, $v) => $q->whereDate('match_date', '<=', $v))
            ->latest('match_date')
            ->paginate((int) $request->query('per_page', 20));
    }

    public function forceDelete(MatchModel $match, ?Request $request = null): string
    {
        $id = $match->id;
        $match->lineups()->delete();
        $match->scorers()->delete();
        $match->delete();

        $this->audit->log('admin_match_force_deleted', 'matches', 'Admin force-deleted match',
            ['match_id' => $id], null, $request);

        return $id;
    }

    public function reassignAuthor(MatchModel $match, string $newAuthorId, ?Request $request = null): MatchModel
    {
        $old = $match->author_id;
        $match->update(['author_id' => $newAuthorId]);

        $this->audit->log('admin_match_reassigned', 'matches', 'Admin reassigned match author',
            ['match_id' => $match->id, 'old_author' => $old, 'new_author' => $newAuthorId],
            $match, $request);

        return $match->fresh()->load(['homeClub', 'awayClub', 'author']);
    }
}
