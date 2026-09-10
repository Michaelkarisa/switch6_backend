<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
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
                'slug'         => $this->slug(['name'=>'Gor Mahia FC','city'=>'Nairobi','founded_year'=>1958]),
                'city'         => 'Nairobi',
                'founded_year' => 1968,
                'stadium'      => 'Nyayo National Stadium',
                'jersey_color' => 0x00FF00, // green
                'logo_url'     => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'AFC Leopards',
                'slug'         => $this->slug(['name'=>'AFC Leopards','city'=>'Nairobi','founded_year'=>1958]),
                'city'         => 'Nairobi',
                'founded_year' => 1958,
                'stadium'      => 'Kasarani Stadium',
                'jersey_color' => 0x0000FF, // blue
                'logo_url'     => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Tusker FC',
                'slug'         => $this->slug(['name'=>'Tusker FC','city'=>'Nairobi','founded_year'=>1969]),
                'city'         => 'Nairobi',
                'founded_year' => 1969,
                'stadium'      => 'Ruaraka Grounds',
                'jersey_color' => 0xFFD700, // gold
                'logo_url'     => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Kakamega Homeboyz',
                'slug'         => $this->slug(['name'=>'Kakamega Homeboyz','city'=>'Kakamega','founded_year'=>2008]),
                'city'         => 'Kakamega',
                'founded_year' => 2008,
                'stadium'      => 'Bukhungu Stadium',
                'jersey_color' => 0xFF0000, // red
                'logo_url'     => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Mathare United',
                'slug'         => $this->slug(['name'=>'Mathare United','city'=>'Nairobi','founded_year'=>1994]),
                'city'         => 'Nairobi',
                'founded_year' => 1994,
                'stadium'      => 'Mathare Social Hall',
                'jersey_color' => 0x800080, // purple
                'logo_url'     => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => (string) Str::uuid(),
                'name'         => 'Ulinzi Stars',
                'slug'         => $this->slug(['name'=>'Ulinzi Stars','city'=>'Nairobi','founded_year'=>1998]),
                'city'         => 'Nairobi',
                'founded_year' => 1998,
                'stadium'      => 'Ulinzi Sports Complex',
                'jersey_color' => 0x008080, // teal
                'logo_url'     => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ];

        DB::table('clubs')->insert($clubs);
    }
   private function slug(array $data):string{
      $slug = "{$data['name']}&{$data['city']}&{$data['founded_year']}";
        return $slug.str_replace(' ', '&',$slug,$slug);
    }
}
