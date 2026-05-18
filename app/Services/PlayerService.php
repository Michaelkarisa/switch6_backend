<?php

namespace App\Services;

use App\Models\Player;

class PlayerService
{
    public function __construct(private AuditLogService $audit) {}

    public function create(array $data): Player
    {
        $player = Player::create($data);
        $this->audit->log('created', 'players', 'Player created', ['player_id' => $player->id], $player);
        return $player;
    }

    public function createMany(array $items): array
    {
        $created = [];
        foreach ($items as $data) $created[] = $this->create($data);
        return $created;
    }

    public function delete(Player $player): void
    {
        $id = $player->id;
        $player->delete();
        $this->audit->log('deleted', 'players', 'Player deleted', ['player_id' => $id]);
    }
}
