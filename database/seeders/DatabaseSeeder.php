<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run order respects foreign-key dependencies:
     *   users → referees → clubs → leagues
     *   → players (needs clubs)
     *   → logins  (needs users)
     *   → matches (needs leagues, clubs, referees, users)
     *   → match lineups / scorers / comments / views (inside MatchSeeder)
     *   → payments / transactions
     *   → advertisements / events
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            UserSeeder::class,
            RefereeSeeder::class,
            ClubSeeder::class,
            LeagueSeeder::class,
            PlayerSeeder::class,
            LoginSeeder::class,
            MatchSeeder::class,
            PlanSeeder::class,          // plans before payments
            PaymentSeeder::class,
            AdvertisementSeeder::class,
            AdEventSeeder::class,
        ]);
    }
}