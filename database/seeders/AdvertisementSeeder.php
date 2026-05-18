<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdvertisementSeeder extends Seeder
{
    public function run(): void
    {
        $ads = [
            [
                'title' => 'Betika Halftime Promo',
                'file_type' => 'video',
                'file_path' => 'ads/betika_halftime.mp4',
                'duration' => 30,
                'period' => 'halftime',
            ],
            [
                'title' => 'SportPesa Pre-Match Banner',
                'file_type' => 'image',
                'file_path' => 'ads/sportpesa_banner.png',
                'duration' => 15,
                'period' => 'before_1st',
            ],
            [
                'title' => 'Safaricom Fulltime Spot',
                'file_type' => 'video',
                'file_path' => 'ads/safaricom_fulltime.mp4',
                'duration' => 30,
                'period' => 'after_2nd',
            ],
            [
                'title' => 'Tusker Matchday Promo',
                'file_type' => 'video',
                'file_path' => 'ads/tusker_matchday.mp4',
                'duration' => 15,
                'period' => 'halftime',
            ],
            [
                'title' => 'KCB Football Banner',
                'file_type' => 'image',
                'file_path' => 'ads/kcb_banner.png',
                'duration' => 15,
                'period' => 'before_1st',
            ],
        ];

        foreach ($ads as $ad) {
            DB::table('advertisements')->insert([
                'id' => (string) Str::uuid(),
                'title' => $ad['title'],
                'file_type' => $ad['file_type'],
                'file_path' => $ad['file_path'],
                'duration' => $ad['duration'],
                'period' => $ad['period'],
                'status' => 'active',
                'end_date' => now()->addMonths(6)->toDateString(),
                'target_tags' => json_encode(['football', 'live']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}