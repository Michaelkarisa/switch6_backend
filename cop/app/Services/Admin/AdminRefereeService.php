<?php

namespace App\Services\Admin;

use App\Models\Referee;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class AdminRefereeService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function paginate(Request $request): LengthAwarePaginator
    {
        $showDeleted = filter_var($request->query('deleted'), FILTER_VALIDATE_BOOLEAN);

        return Referee::query()
            ->when($showDeleted, fn ($q) => $q->onlyTrashed())
            ->when($request->query('search'), fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('nationality', 'like', "%{$s}%")
            )
            ->withCount('matches')
            ->latest()
            ->paginate((int) $request->query('per_page', 20));
    }

    public function store(array $data, ?Request $request = null): Referee
    {
        $referee = Referee::create($data);

        $this->audit->log('admin_referee_created', 'referees', 'Admin created referee',
            ['referee_id' => $referee->id, 'name' => $referee->name], $referee, $request);

        return $referee;
    }

    public function update(Referee $referee, array $data, ?Request $request = null): Referee
    {
        $referee->update($data);

        $this->audit->log('admin_referee_updated', 'referees', 'Admin updated referee',
            ['referee_id' => $referee->id], $referee, $request);

        return $referee->fresh();
    }

    public function delete(Referee $referee, ?Request $request = null): void
    {
        $id = $referee->id;
        $referee->delete(); // soft delete

        $this->audit->log('admin_referee_deleted', 'referees', 'Admin soft-deleted referee',
            ['referee_id' => $id], null, $request);
    }

    public function restore(Referee $referee, ?Request $request = null): Referee
    {
        $referee->restore();

        $this->audit->log('admin_referee_restored', 'referees', 'Admin restored referee',
            ['referee_id' => $referee->id], $referee, $request);

        return $referee->fresh();
    }

    public function forceDelete(Referee $referee, ?Request $request = null): void
    {
        $id = $referee->id;
        $referee->forceDelete();

        $this->audit->log('admin_referee_force_deleted', 'referees', 'Admin permanently deleted referee',
            ['referee_id' => $id], null, $request);
    }
}
