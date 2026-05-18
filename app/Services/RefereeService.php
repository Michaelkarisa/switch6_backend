<?php

namespace App\Services;

use App\Models\Referee;

class RefereeService
{
    public function __construct(private AuditLogService $audit) {}

    public function list(?string $search = null)
    {
        return Referee::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Referee
    {
        $referee = Referee::create($data);
        $this->audit->log('created', 'referees', 'Referee created', ['referee_id' => $referee->id], $referee);
        return $referee;
    }

    public function delete(Referee $referee): void
    {
        $id = $referee->id;
        $referee->delete();
        $this->audit->log('deleted', 'referees', 'Referee deleted', ['referee_id' => $id]);
    }
}
