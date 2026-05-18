<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeagueSeeder extends Seeder
{
    public function run(): void
    {
        $leagues = [
            [
                'id'         => (string) Str::uuid(),
                'leaguename' => 'Football Kenya Federation Premier League',
                'name'       => 'FKF Premier League',
                'type'       => 'league',
                'logo'       => null,
                'timestamp'  => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => (string) Str::uuid(),
                'leaguename' => 'Football Kenya Federation Shield Cup',
                'name'       => 'FKF Shield Cup',
                'type'       => 'cup',
                'logo'       => null,
                'timestamp'  => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => (string) Str::uuid(),
                'leaguename' => 'Football Kenya Federation Division One',
                'name'       => 'FKF Division One',
                'type'       => 'league',
                'logo'       => null,
                'timestamp'  => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('leagues')->insert($leagues);
    }
}
