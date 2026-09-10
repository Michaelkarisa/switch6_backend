<?php

namespace App\Services\Admin;

use App\Models\League;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class AdminLeagueService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function paginate(Request $request): LengthAwarePaginator
    {
        $showDeleted = filter_var($request->query('deleted'), FILTER_VALIDATE_BOOLEAN);

        return League::query()
            ->when($showDeleted, fn ($q) => $q->onlyTrashed())
            ->when($request->query('search'), fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('short_name', 'like', "%{$s}%")
            )
            ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
            ->withCount('matches')
            ->latest()
            ->paginate((int) $request->query('per_page', 20));
    }

    public function store(array $data, ?Request $request = null): League
    {
        $league = League::create($data);

        $this->audit->log('admin_league_created', 'leagues', 'Admin created league',
            ['league_id' => $league->id, 'name' => $league->name], $league, $request);

        return $league;
    }

    public function update(League $league, array $data, ?Request $request = null): League
    {
        $league->update($data);

        $this->audit->log('admin_league_updated', 'leagues', 'Admin updated league',
            ['league_id' => $league->id], $league, $request);

        return $league->fresh();
    }

    public function delete(League $league, ?Request $request = null): void
    {
        if ($league->matches()->exists()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Cannot delete a league that has matches. Delete or reassign the matches first.',
                'data'    => null,
            ], 409));
        }

        $id = $league->id;
        $league->delete(); // soft delete

        $this->audit->log('admin_league_deleted', 'leagues', 'Admin soft-deleted league',
            ['league_id' => $id], null, $request);
    }

    public function restore(League $league, ?Request $request = null): League
    {
        $league->restore();

        $this->audit->log('admin_league_restored', 'leagues', 'Admin restored league',
            ['league_id' => $league->id], $league, $request);

        return $league->fresh();
    }

    public function forceDelete(League $league, ?Request $request = null): void
    {
        $id = $league->id;
        $league->forceDelete();

        $this->audit->log('admin_league_force_deleted', 'leagues', 'Admin permanently deleted league',
            ['league_id' => $id], null, $request);
    }
}
