<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefereeSeeder extends Seeder
{
    public function run(): void
    {
        $referees = [
            [
                'id'          => (string) Str::uuid(),
                'name'        => 'Samuel Odhiambo',
                'phone'       => '+254700111001',
                'nationality' => 'Kenyan',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => (string) Str::uuid(),
                'name'        => 'David Ngugi',
                'phone'       => '+254711111002',
                'nationality' => 'Kenyan',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => (string) Str::uuid(),
                'name'        => 'Felix Mutua',
                'phone'       => '+254722111003',
                'nationality' => 'Kenyan',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => (string) Str::uuid(),
                'name'        => 'Charles Mwenda',
                'phone'       => '+254733111004',
                'nationality' => 'Ugandan',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => (string) Str::uuid(),
                'name'        => 'Bernard Kiplimo',
                'phone'       => '+254744111005',
                'nationality' => 'Kenyan',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        DB::table('referees')->insert($referees);
    }
}
