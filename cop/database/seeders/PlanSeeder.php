<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'               => 'Standard',
                'slug'               => 'standard',
                'description'        => 'Full broadcast pipeline for growing clubs and broadcasters.',
                'price'              => 1025,
                'duration_days'      => 30,
                'max_matches'        => 15,
                'max_cameras'        => 4,
                'quality'            => [480,720,1080,2160],
                'ads_enabled'        => true,
                'analytics_enabled'  => true,
                'is_active'          => true,
                'features'           => ['lineup_builder' => true, 'live_score' => true, 'sponsor_overlay' => true],
                'most_popular'       => true,
            ],
            [
                'name'               => 'Pro',
                'slug'               => 'pro',
                'description'        => 'More matches, cameras, and white-label options for leagues.',
                'price'              => 2275,
                'duration_days'      => 30,
                'max_matches'        => 30,
                'max_cameras'        => 12,
                'quality'            => [480,720,1080,2160],
                'ads_enabled'        => true,
                'analytics_enabled'  => true,
                'is_active'          => true,
                'features'           => [
                    'lineup_builder'  => true,
                    'live_score'      => true,
                    'sponsor_overlay' => true,
                    'white_label'     => true,
                    'api_access'      => true,
                ],
                'most_popular'       => false,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['slug' => $plan['slug']], array_merge(
                ['id' => (string) Str::uuid()],
                $plan,
            ));
        }

        $this->command->info('Plans seeded: Standard, Pro');
    }
}