<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class HealthService
{
    public function health(): array
    {
        return ['status' => 'healthy', 'timestamp' => now()->toIso8601String(), 'version' => '2.0.0'];
    }

    public function databaseStatus(): array
    {
        try {
            $result = DB::selectOne('SELECT 1 as test');
            return ['status' => 'connected', 'pool_available' => true, 'test_query' => $result];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage(), 'pool_available' => false];
        }
    }
}
