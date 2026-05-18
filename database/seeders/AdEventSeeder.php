<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdEventSeeder extends Seeder
{
    public function run(): void
    {
        $adIds = DB::table('advertisements')->pluck('id');
        $matchIds = DB::table('matches')->pluck('id');

        if ($adIds->isEmpty() || $matchIds->isEmpty()) {
            return;
        }

        foreach (range(1, 30) as $i) {
            DB::table('ad_events')->insert([
                'id' => (string) Str::uuid(),
                'advertisement_id' => $adIds->random(),
                'match_id' => $matchIds->random(),
                'event_type' => collect(['injected', 'played', 'completed'])->random(),
                'period' => collect(['before_1st', 'halftime', 'after_2nd'])->random(),
                'play_time' => rand(10, 30),
                'viewer_count' => rand(50, 1200),
                'platform' => collect(['youtube', 'facebook', 'rtmp_custom'])->random(),
                'created_at' => now()->subDays(rand(0, 20)),
                'updated_at' => now(),
            ]);
        }
    }
}