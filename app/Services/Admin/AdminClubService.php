<?php

namespace App\Services\Admin;

use App\Models\Club;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class AdminClubService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function paginate(Request $request): LengthAwarePaginator
    {
        $showDeleted = filter_var($request->query('deleted'), FILTER_VALIDATE_BOOLEAN);

        return Club::query()
            ->when($showDeleted, fn ($q) => $q->onlyTrashed())
            ->when($request->query('search'), fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%")
            )
            ->withCount('players')
            ->latest()
            ->paginate((int) $request->query('per_page', 20));
    }

    public function store(array $data, ?Request $request = null): Club
    {
        $club = Club::create($data);

        $this->audit->log('admin_club_created', 'clubs', 'Admin created club',
            ['club_id' => $club->id, 'name' => $club->name], $club, $request);

        return $club;
    }

    public function update(Club $club, array $data, ?Request $request = null): Club
    {
        $club->update($data);

        $this->audit->log('admin_club_updated', 'clubs', 'Admin updated club',
            ['club_id' => $club->id], $club, $request);

        return $club->fresh();
    }

    public function delete(Club $club, ?Request $request = null): void
    {
        $id = $club->id;
        $club->delete(); // soft delete

        $this->audit->log('admin_club_deleted', 'clubs', 'Admin soft-deleted club',
            ['club_id' => $id], null, $request);
    }

    public function restore(Club $club, ?Request $request = null): Club
    {
        $club->restore();

        $this->audit->log('admin_club_restored', 'clubs', 'Admin restored club',
            ['club_id' => $club->id], $club, $request);

        return $club->fresh();
    }

    public function forceDelete(Club $club, ?Request $request = null): void
    {
        $id = $club->id;
        $club->forceDelete();

        $this->audit->log('admin_club_force_deleted', 'clubs', 'Admin permanently deleted club',
            ['club_id' => $id], null, $request);
    }
}
