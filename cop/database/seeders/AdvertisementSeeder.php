<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class AdvertisementSeeder extends Seeder
{
    public function run(): void
    {
        $broadcasterIds = User::whereHas('roles', function ($q) {
            $q->where('name', 'broadcaster');
        })->pluck('id');

        $advertiserIds = User::whereHas('roles', function ($q) {
            $q->where('name', 'advertiser');
        })->pluck('id');

        $ads = [
            // ── Video Ads ─────────────────────────────────────────────────────
            [
                'title' => 'Betika Halftime Promo',
                'file_type' => 'video',
                'file_path' => 'ads/betika_halftime.mp4',
                'duration' => 30,
                'period' => 'halftime',
                'target_tags' => ['football', 'live', 'betting'],
                'status' => 'active',
                'user_id' => $advertiserIds->random(),
            ],
            [
                'title' => 'SportPesa Pre-Match Hype',
                'file_type' => 'video',
                'file_path' => 'ads/sportpesa_prematch.mp4',
                'duration' => 45,
                'period' => 'before_1st',
                'target_tags' => ['football', 'prematch', 'betting'],
                'status' => 'active',
                'user_id' => $advertiserIds->random(),
            ],
            [
                'title' => 'Mozzart Bet Live Odds Flash',
                'file_type' => 'video',
                'file_path' => 'ads/mozzart_live_odds.mp4',
                'duration' => 20,
                'period' => 'during_live',
                'target_tags' => ['football', 'live', 'odds'],
                'status' => 'active',
                'user_id' => $advertiserIds->random(),
            ],
            [
                'title' => '1xBet Post-Match Recap Sponsor',
                'file_type' => 'video',
                'file_path' => 'ads/1xbet_recap.mp4',
                'duration' => 35,
                'period' => 'after_2nd',
                'target_tags' => ['football', 'recap', 'highlights'],
                'status' => 'active',
                'user_id' => $advertiserIds->random(),
            ],
            [
                'title' => 'Red Bull Energy Break',
                'file_type' => 'video',
                'file_path' => 'ads/redbull_energy.mp4',
                'duration' => 15,
                'period' => 'halftime',
                'target_tags' => ['sports', 'energy', 'youth'],
                'status' => 'active',
                'user_id' => $advertiserIds->random(),
            ],
            [
                'title' => 'Nike Football Boots Launch',
                'file_type' => 'video',
                'file_path' => 'ads/nike_boots_launch.mp4',
                'duration' => 60,
                'period' => 'before_1st',
                'target_tags' => ['football', 'gear', 'premium'],
                'status' => 'pending',
                'user_id' => $advertiserIds->random(),
            ],

            // ── Image Ads ─────────────────────────────────────────────────────
            [
                'title' => 'SportPesa Pre-Match Banner',
                'file_type' => 'image',
                'file_path' => 'ads/sportpesa_banner.png',
                'duration' => 15,
                'period' => 'before_1st',
                'target_tags' => ['football', 'banner'],
                'status' => 'active',
                'user_id' => $advertiserIds->random(),
            ],
            [
                'title' => 'Betika Jackpot Sidebar',
                'file_type' => 'image',
                'file_path' => 'ads/betika_jackpot_sidebar.jpg',
                'duration' => 10,
                'period' => 'during_live',
                'target_tags' => ['football', 'jackpot', 'sidebar'],
                'status' => 'active',
                'user_id' => $advertiserIds->random(),
            ],
            [
                'title' => 'Puma Match Day Collection',
                'file_type' => 'image',
                'file_path' => 'ads/puma_matchday.jpg',
                'duration' => 12,
                'period' => 'halftime',
                'target_tags' => ['fashion', 'football', 'apparel'],
                'status' => 'active',
                'user_id' => $advertiserIds->random(),
            ],
            [
                'title' => 'Safaricom Data Bundle Promo',
                'file_type' => 'image',
                'file_path' => 'ads/safaricom_data.png',
                'duration' => 10,
                'period' => 'before_1st',
                'target_tags' => ['telecom', 'data', 'streaming'],
                'status' => 'active',
                'user_id' => $advertiserIds->random(),
            ],
            [
                'title' => 'Adidas Predator Spotlight',
                'file_type' => 'image',
                'file_path' => 'ads/adidas_predator.jpg',
                'duration' => 15,
                'period' => 'after_2nd',
                'target_tags' => ['football', 'boots', 'premium'],
                'status' => 'paused',
                'user_id' => $advertiserIds->random(),
            ],
            [
                'title' => 'Tusker Lager Match Celebration',
                'file_type' => 'image',
                'file_path' => 'ads/tusker_celebration.png',
                'duration' => 12,
                'period' => 'after_2nd',
                'target_tags' => ['beer', 'celebration', 'local'],
                'status' => 'active',
                'user_id' => $advertiserIds->random(),
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
                'status' => $ad['status'],
                'end_date' => now()->addMonths(rand(3, 12))->toDateString(),
                'target_tags' => json_encode($ad['target_tags']),
                'created_at' => now(),
                'updated_at' => now(),
                'user_id' => $ad['user_id'],
            ]);
        }
    }
}