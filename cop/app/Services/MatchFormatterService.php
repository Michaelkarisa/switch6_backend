<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubManager;
use App\Models\Lineup;
use App\Models\MatchModel;
use App\Models\Player;
use App\Models\User;

class MatchFormatterService
{
    public function format(MatchModel $match): array
    {
        $match->loadMissing([
            'league',
            'homeClub',
            'awayClub',
            'referee',
            'author',
            'lineups.player',
            'lineups.club',
        ]);

        return [
            'id' => $match->id,
            'slug' => $match->slug,

            'league' => [
                'id'=> $match->league->id ?? '',
                'name'=> $match->league->short_name ??'Friendly',
                'url'=> $match->league->logo_url??'',
            ],

            'date' => $match->match_date?->format('Y-m-d') ?? '',
            'time' => $match->match_date?->format('H:i') ?? '',

            'stadium' => $match->venue ?? '',
            'status' => $match->status,
            'views' => $match->matchViews()??0,
            'camera' => $this->cameras($match->author),
            'quality' => $this->quality($match->author),
            'type'   => $match->author?->game_type ?? '',
            // ✅ AUTHOR ADDED (Arena Stream requirement)
            'author' => [
                'id' => $match->author?->id ?? '',
                'name' => $match->author?->name ?? '',
            ],

            'referee' => [
                'id' => $match->referee_id ?? '',
                'name' => $match->referee?->name ?? '',
                'url' => $match->referee?->profile_url ?? '',
            ],

            'homeTeam' => [
                'id' => $match->home_club_id,
                'name' => $match->homeClub?->name ?? '',
                'managers' => $this->managers($match->homeClub),
                'formation' => $match->home_formation ?: '4-4-2',
                'url' => $match->homeClub?->logo_url ?? '',
                'color' => $match->homeClub?->jersey_color,
                'startingPlayers' => $this->players($match, $match->home_club_id, true),
                'substitutes' => $this->players($match, $match->home_club_id, false),
                'goals' => (int) ($match->home_score ?? 0),
            ],

            'awayTeam' => [
                'id' => $match->away_club_id,
                'name' => $match->awayClub?->name ?? '',
                'managers' => $this->managers($match->awayClub),
                'formation' => $match->away_formation ?: '4-4-2',
                'url' => $match->awayClub?->logo_url ?? '',
                'color' => $match->awayClub?->jersey_color,
                'startingPlayers' => $this->players($match, $match->away_club_id, true),
                'substitutes' => $this->players($match, $match->away_club_id, false),
                'goals' => (int) ($match->away_score ?? 0),
            ],
        ];
    }
   private function cameras(User $author):int{
     $c = $author->cameras();
     return $c??1;
   }
   private function quality(User $user):int{
     $q = $user->quality();
     return $q??480;
   }
   private function managers(Club $club){
   $managers = $club->managers()
               ->latest()
               ->get()
               ->map(function (ClubManager $manager) {
                return [
                    'id' => $manager->id ?? '',
                    'name' => $manager->name ?? '',
                    'url' => $manager->profile_url ??'',
                    'role' => $manager->role ??'',
                ];
            })->values()->toArray();
   return $managers;
   }

    private function players(MatchModel $match, ?string $clubId, bool $starter): array
    {
        if (! $clubId) {
            return [];
        }

        return $match->lineups
            ->where('club_id', $clubId)
            ->where('is_starter', $starter)
            ->sortBy(fn ($lineup) =>
                $this->positionOrder($lineup->position) .
                '-' .
                ($lineup->player?->jersey_number ?? 999)
            )
            ->map(function (Lineup $lineup) use ($match) {
                return [
                    'id' => $lineup->player?->id ?? '',
                    'name' => $lineup->player?->name ?? '',
                    'number' => $lineup->player?->jersey_number,
                    'position' => $lineup->position,
                    'team' => $lineup->club?->name ?? '',
                    'lineup_id' => $lineup->id ?? '',
                    'card' => $this->card($lineup->player, $match),
                    'isCaptain' => $this->playerIsCaptain($lineup->player),
                    'url' => $lineup->player->profile_url,
                ];
            })
            ->values()
            ->all();
    }

    private function card(Player $player, MatchModel $match): string{
        if($match->league){
       $card = $player->card($match->league->id);
       if($card){
         return $card->card;
       }
        }
     return 'None';
    }
    private function playerIsCaptain(Player $player,):bool{
    if(!$player->role){
         return false;
    }
      return $player->role == "captain";
    }
    private function positionOrder(?string $position): int
    {
        return match ($position) {
            'GK' => 1,

            'RB', 'RWB' => 2,
            'CB' => 3,
            'LB', 'LWB' => 4,

            'DM', 'CDM' => 5,
            'CM' => 6,
            'CAM', 'AM' => 7,

            'RM' => 8,
            'LM' => 9,

            'RW' => 10,
            'LW' => 11,

            'CF', 'ST' => 12,

            default => 99,
        };
    }
}