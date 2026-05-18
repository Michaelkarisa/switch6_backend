<?php

namespace App\Services;

use App\Models\MatchView;

class MatchViewService
{
    public function __construct(private AuditLogService $audit) {}

    public function create(array $data): MatchView
    {
        $view = MatchView::create($data);
        $this->audit->log('created', 'match_views', 'Match view recorded', ['view_id' => $view->id], $view);
        return $view;
    }
}
