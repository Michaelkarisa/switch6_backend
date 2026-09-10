<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeagueSeeder extends Seeder
{
    public function run(): void
    {
        $leagues = [
            [
                'id'         => (string) Str::uuid(),
                'name'       => 'Football Kenya Federation Premier League',
                'short_name' => 'FKF Premier League',
                'type'       => 'league',
                'slug'       => $this->slug('Football Kenya Federation Premier League'),
                'logo_url'   => null,
                'timestamp'  => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => (string) Str::uuid(),
                'name'       => 'Football Kenya Federation Shield Cup',
                'short_name' => 'FKF Shield Cup',
                'slug'       => $this->slug('Football Kenya Federation Shield Cup'),
                'type'       => 'cup',
                'logo_url'   => null,
                'timestamp'  => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => (string) Str::uuid(),
                'name'       => 'Football Kenya Federation Division One',
                'short_name' => 'FKF Division One',
                'type'       => 'league',
                'slug'       => $this->slug('Football Kenya Federation Division One'),
                'logo_url'   => null,
                'timestamp'  => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('leagues')->insert($leagues);
    }

      private function slug(string $name):string{
        $time = Carbon::now()->toDateTimeString();
        $slug = "{$name} {$time}";
        return $slug.str_replace(' ', '&',$slug,$slug);
    }

}
