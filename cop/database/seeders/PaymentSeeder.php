<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $users = DB::table('users')->pluck('id');

        // ── Payments ──────────────────────────────────────────────────────────
        $payments = [
            [
                'id'         => (string) Str::uuid(),
                'phone'      => '+254700000001',
                'amount'     => 500,
                'user_id'    => $users->random(),
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'id'         => (string) Str::uuid(),
                'phone'      => '+254711000002',
                'amount'     => 1000,
                'user_id'    => $users->random(),
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'id'         => (string) Str::uuid(),
                'phone'      => '+254722000003',
                'amount'     => 250,
                'user_id'    => $users->random(),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
        ];

        DB::table('payments')->insert($payments);

    }
}
