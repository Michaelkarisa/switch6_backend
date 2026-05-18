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
                'name'               => 'Starter',
                'slug'               => 'starter',
                'description'        => 'Perfect for small clubs getting started with digital match ops.',
                'price_kes'          => 0,
                'duration_days'      => 30,
                'max_matches'        => 5,
                'max_streams'        => 1,
                'ads_enabled'        => false,
                'analytics_enabled'  => false,
                'is_active'          => true,
                'features'           => ['lineup_builder' => true, 'live_score' => true],
                'most_popular'       => false,
            ],
            [
                'name'               => 'Standard',
                'slug'               => 'standard',
                'description'        => 'Full broadcast pipeline for growing clubs and broadcasters.',
                'price_kes'          => 1125,
                'duration_days'      => 30,
                'max_matches'        => 30,
                'max_streams'        => 5,
                'ads_enabled'        => true,
                'analytics_enabled'  => true,
                'is_active'          => true,
                'features'           => ['lineup_builder' => true, 'live_score' => true, 'sponsor_overlay' => true],
                'most_popular'       => true,
            ],
            [
                'name'               => 'Pro',
                'slug'               => 'pro',
                'description'        => 'Unlimited matches, streams, and white-label options for leagues.',
                'price_kes'          => 2375,
                'duration_days'      => 30,
                'max_matches'        => null,
                'max_streams'        => null,
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

        $this->command->info('Plans seeded: Starter, Standard, Pro');
    }
}