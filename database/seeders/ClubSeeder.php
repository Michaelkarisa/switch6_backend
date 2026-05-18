<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClubSeeder extends Seeder
{
    public function run(): void
    {
        $clubs = [
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Gor Mahia FC',
                'city'         => 'Nairobi',
                'founded_year' => 1968,
                'stadium'      => 'Nyayo National Stadium',
                'manager'      => 'Sammy Omollo',
                'jersey_color' => 0x00FF00, // green
                'logo'         => null,
                'logo_url'     => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'AFC Leopards',
                'city'         => 'Nairobi',
                'founded_year' => 1958,
                'stadium'      => 'Kasarani Stadium',
                'manager'      => 'Patrick Aussems',
                'jersey_color' => 0x0000FF, // blue
                'logo'         => null,
                'logo_url'     => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Tusker FC',
                'city'         => 'Nairobi',
                'founded_year' => 1969,
                'stadium'      => 'Ruaraka Grounds',
                'manager'      => 'Robert Matano',
                'jersey_color' => 0xFFD700, // gold
                'logo'         => null,
                'logo_url'     => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Kakamega Homeboyz',
                'city'         => 'Kakamega',
                'founded_year' => 2008,
                'stadium'      => 'Bukhungu Stadium',
                'manager'      => 'Johnmark Makwatta',
                'jersey_color' => 0xFF0000, // red
                'logo'         => null,
                'logo_url'     => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Mathare United',
                'city'         => 'Nairobi',
                'founded_year' => 1994,
                'stadium'      => 'Mathare Social Hall',
                'manager'      => 'Francis Kimanzi',
                'jersey_color' => 0x800080, // purple
                'logo'         => null,
                'logo_url'     => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Ulinzi Stars',
                'city'         => 'Nairobi',
                'founded_year' => 1998,
                'stadium'      => 'Ulinzi Sports Complex',
                'manager'      => 'Benjamin Nyangweso',
                'jersey_color' => 0x008080, // teal
                'logo'         => null,
                'logo_url'     => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ];

        DB::table('clubs')->insert($clubs);
    }
}
