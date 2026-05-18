<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $users = DB::table('users')->pluck('phone', 'id');

        // ── Payments ──────────────────────────────────────────────────────────
        $payments = [
            [
                'id'         => (string) Str::uuid(),
                'phone'      => '+254700000001',
                'amount'     => 500,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'id'         => (string) Str::uuid(),
                'phone'      => '+254711000002',
                'amount'     => 1000,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'id'         => (string) Str::uuid(),
                'phone'      => '+254722000003',
                'amount'     => 250,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
        ];

        DB::table('payments')->insert($payments);

        // ── Transactions ──────────────────────────────────────────────────────
        $transactions = [
            [
                'id'         => (string) Str::uuid(),
                'phone'      => '+254700000001',
                'amount'     => 500,
                'status'     => 'completed',
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'id'         => (string) Str::uuid(),
                'phone'      => '+254711000002',
                'amount'     => 1000,
                'status'     => 'completed',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'id'         => (string) Str::uuid(),
                'phone'      => '+254722000003',
                'amount'     => 250,
                'status'     => 'pending',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'id'         => (string) Str::uuid(),
                'phone'      => '+254733000004',
                'amount'     => 750,
                'status'     => 'failed',
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ];

        DB::table('transactions')->insert($transactions);
    }
}
