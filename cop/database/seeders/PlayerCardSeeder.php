<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlayerCardSeeder extends Seeder
{
    public function run(): void
    {
        $players = DB::table('players')->pluck('id')->take(10);
        $leagues = DB::table('leagues')->pluck('id');
        $matches = DB::table('matches')->pluck('id');
        $minutes = collect([21,30,48,56,69,78,88]);
        $cards    = collect(["Yellow", "Red"]);
        $playerCards = [];
          foreach ($players as $i => $p) {
                $playerCards[] = [
                    'id' => (string) Str::uuid(),
                    'league_id' => $leagues->random(),
                    'match_id' =>  $matches->random(),
                    'player_id' => $players->random(),
                    'minute'   =>  $minutes->random(),
                    'card'     =>  $cards->random(),

                ];

          }

          DB::table('player_cards')->insert($playerCards);
    }

}