<?php

namespace Database\Seeders;

use App\Models\Player;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Exists;

class MatchSeeder extends Seeder
{
    public function run(): void
    {
        $clubs    = DB::table('clubs')->pluck('id', 'name');
        $leagues  = DB::table('leagues')->pluck('id');
        $referees = DB::table('referees')->pluck('id', 'name');
        $users    = DB::table('users')->pluck('id', 'email');

        $playersByClub = DB::table('players')
            ->get()
            ->groupBy('club_id');

        //$leagueId = $leagues['Football Kenya Federation Premier League'];
        $authorId = $users['michaelkarisa49@gmail.com'];

        $refList = array_values($referees->toArray());
        $clubList = array_values($clubs->toArray());
         $this->command->info("Clubs: {$clubs}");
         $this->command->info("Users: {$users}");
        // $this->command->info("ClubList: {$clubList}");
        // ─────────────────────────────────────────────
        // HELPERS
        // ─────────────────────────────────────────────
        $pickPlayers = function ($players, $count) {
            $list = $players->values();

            // fallback safe cycle if not enough players
            if ($list->count() < $count) {
                $list = $list->merge($list)->merge($list);
            }

            return $list->take($count);
        };

        $makeLineups = function ($matchId, $clubId, $players, $formation) {

            $xiPositions = match ($formation) {
                '4-3-3' => ['GK','RB','CB','CB','LB','CM','CM','CM','RW','ST','LW'],
                '4-4-2' => ['GK','RB','CB','CB','LB','RM','CM','CM','LM','ST','ST'],
                default => ['GK','RB','CB','CB','LB','CM','CM','CM','RW','ST','LW'],
            };

            $lineups = [];

            $xi = $players->take(11);
            $subs = $players->skip(11)->take(4); // max 5 subs
            
            foreach ($xi as $i => $p) {
                $lineups[] = [
                    'id'         => (string) Str::uuid(),
                    'match_id'   => $matchId,
                    'player_id'  => $p->id,
                    'club_id'    => $clubId,
                    'position'   => $this->position($p,$xiPositions,$i),
                    'is_starter' => true,
                    'minute_in'  => null,
                    'minute_out' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach ($subs as $p) {
                $lineups[] = [
                    'id'         => (string) Str::uuid(),
                    'match_id'   => $matchId,
                    'player_id'  => $p->id,
                    'club_id'    => $clubId,
                    'position'   => $p->position,
                    'is_starter' => false,
                    'minute_in'  => null,
                    'minute_out' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            return $lineups;
        };

        // ─────────────────────────────────────────────
        // MATCH SCENARIOS (5 REALISTIC MATCHES)
        // ─────────────────────────────────────────────
        $fixtures = [
            ['home'=>0,'away'=>1,'status'=>'completed','score'=>[2,1],'venue'=>'Nyayo Stadium'],
            ['home'=>2,'away'=>3,'status'=>'completed','score'=>[1,1],'venue'=>'Ruaraka Grounds'],
            ['home'=>1,'away'=>2,'status'=>'completed','score'=>[0,3],'venue'=>'Kasarani Stadium'],
            ['home'=>0,'away'=>2,'status'=>'live','score'=>[1,0],'venue'=>'Nyayo Stadium'],
            ['home'=>3,'away'=>1,'status'=>'scheduled','score'=>[0,0],'venue'=>'Bukhungu Stadium'],
        ];

        $allLineups = [];

        foreach ($fixtures as $fixture) {

            $matchId = (string) Str::uuid();

            $homeId = $clubList[$fixture['home']];
            $awayId = $clubList[$fixture['away']];

            $homePlayers = $playersByClub[$homeId] ?? collect([]);
            $awayPlayers = $playersByClub[$awayId] ?? collect([]);
          
             $ch    = DB::table('clubs')->find($homeId);
             $ca    = DB::table('clubs')->find($awayId);
            $this->command->info("HomeTeam. Name: {$ch->name}");
            $this->command->info("AwayTeam. Name: {$ca->name}");
           
            DB::table('matches')->insert([
                'id'             => $matchId,
                'league_id'      => $leagues->random(),
                'slug'           => $this->slug($ch->name,$ca->name),
                'home_club_id'   => $homeId,
                'away_club_id'   => $awayId,
                'referee_id'     => $refList[array_rand($refList)],
                'author_id'      => $authorId,
                'match_date'     => now()->subDays(rand(1, 10)),
                'home_score'     => $fixture['score'][0],
                'away_score'     => $fixture['score'][1],
                'status'         => $fixture['status'],
                'venue'          => $fixture['venue'],
                'home_formation' => '4-3-3',
                'away_formation' => '4-4-2',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            DB::table('match_views')->insert([
                 'id'                   => (string) Str::uuid(),
                 'match_id'             => $matchId,
                 'view_count'           => random_int(10,10000),
                 'platform'             => collect(['youtube','facebook'])->random(),
                 'created_at'           => now(),
            ]);
            // HOME LINEUP
            $allLineups = array_merge(
                $allLineups,
                $makeLineups($matchId, $homeId, $pickPlayers($homePlayers, 16), '4-3-3')
            );

            // AWAY LINEUP
            $allLineups = array_merge(
                $allLineups,
                $makeLineups($matchId, $awayId, $pickPlayers($awayPlayers, 16), '4-4-2')
            );
        }

        DB::table('lineups')->insert($allLineups);
    }

    private function position($player,array $positions,$i){
        $position = $player->position;
        $istrue =  collect($positions)->map(fn($p)=>$this->checkPos($p,$position));
       if($istrue){
        return $position;
       }
       return $positions[$i];
    }

    private function checkPos($p,$position){
            if($p == $position){
                return true;
            }else{
                return false;
            }
    }

     private function slug(string $awayClub,string $homeClub):string{
        $time = Carbon::now()->toDateTimeString();
        $slug = "{$homeClub} VS {$awayClub} {$time}";
        return $slug.str_replace(' ', '&',$slug,$slug);
    }

}