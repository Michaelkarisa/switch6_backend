<?php

namespace App\Services\Admin;

use App\Models\Player;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class AdminPlayerService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function paginate(Request $request): LengthAwarePaginator
    {
        $showDeleted = filter_var($request->query('deleted'), FILTER_VALIDATE_BOOLEAN);

        return Player::query()
            ->with('club:id,name')
            ->when($showDeleted, fn ($q) => $q->onlyTrashed())
            ->when($request->query('search'), fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('nationality', 'like', "%{$s}%")
            )
            ->when($request->query('club_id'),  fn ($q, $v) => $q->where('club_id', $v))
            ->when($request->query('position'), fn ($q, $v) => $q->where('position', $v))
            ->latest()
            ->paginate((int) $request->query('per_page', 20));
    }

    public function store(array $data, ?Request $request = null): Player
    {
        $player = Player::create($data);

        $this->audit->log('admin_player_created', 'players', 'Admin created player',
            ['player_id' => $player->id, 'name' => $player->name], $player, $request);

        return $player->load('club:id,name');
    }

    public function update(Player $player, array $data, ?Request $request = null): Player
    {
        $player->update($data);

        $this->audit->log('admin_player_updated', 'players', 'Admin updated player',
            ['player_id' => $player->id], $player, $request);

        return $player->fresh()->load('club:id,name');
    }

    public function delete(Player $player, ?Request $request = null): void
    {
        $id = $player->id;
        $player->delete(); // soft delete

        $this->audit->log('admin_player_deleted', 'players', 'Admin soft-deleted player',
            ['player_id' => $id], null, $request);
    }

    public function restore(Player $player, ?Request $request = null): Player
    {
        $player->restore();

        $this->audit->log('admin_player_restored', 'players', 'Admin restored player',
            ['player_id' => $player->id], $player, $request);

        return $player->fresh()->load('club:id,name');
    }

    public function forceDelete(Player $player, ?Request $request = null): void
    {
        $id = $player->id;
        $player->forceDelete();

        $this->audit->log('admin_player_force_deleted', 'players', 'Admin permanently deleted player',
            ['player_id' => $id], null, $request);
    }
}
