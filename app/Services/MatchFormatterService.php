<?php

namespace App\Services;

use App\Models\Lineup;
use App\Models\MatchModel;

class MatchFormatterService
{
    public function format(MatchModel $match): array
    {
        $match->loadMissing([
            'league',
            'homeClub',
            'awayClub',
            'refereeRecord',
            'author',
            'lineups.player',
            'lineups.club',
        ]);

        return [
            'id' => $match->id,

            'league' => $match->league?->leaguename
                ?? $match->league?->name
                ?? '',

            'date' => $match->match_date?->format('Y-m-d') ?? '',
            'time' => $match->match_date?->format('H:i') ?? '',

            'stadium' => $match->venue ?? '',
            'status' => $match->status,
            'viewers' => 0,
            'camera' => $match->author?->camera ?? 6,

            // ✅ AUTHOR ADDED (Arena Stream requirement)
            'author' => [
                'id' => $match->author?->id ?? '',
                'name' => $match->author?->name ?? '',
                'email' => $match->author?->email ?? '',
                'phone' => $match->author?->phone ?? '',
                'camera' => $match->author?->camera ?? 6,
            ],

            'referee' => [
                'id' => $match->referee_id ?? $match->referee,
                'name' => $match->refereeRecord?->name ?? '',
                'phone' => $match->refereeRecord?->phone ?? '',
            ],

            'homeTeam' => [
                'id' => $match->home_club_id,
                'name' => $match->homeClub?->name ?? '',
                'formation' => $match->home_formation ?: '4-4-2',
                'urlA' => $match->homeClub?->logo_url ?? '',
                'color' => $match->homeClub?->jersey_color,
                'startingPlayers' => $this->players($match, $match->home_club_id, true),
                'substitutes' => $this->players($match, $match->home_club_id, false),
                'goals' => (int) ($match->home_score ?? 0),
            ],

            'awayTeam' => [
                'id' => $match->away_club_id,
                'name' => $match->awayClub?->name ?? '',
                'formation' => $match->away_formation ?: '4-4-2',
                'urlB' => $match->awayClub?->logo_url ?? '',
                'color' => $match->awayClub?->jersey_color,
                'startingPlayers' => $this->players($match, $match->away_club_id, true),
                'substitutes' => $this->players($match, $match->away_club_id, false),
                'goals' => (int) ($match->away_score ?? 0),
            ],
        ];
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
            ->map(function (Lineup $lineup) {
                return [
                    'id' => $lineup->player?->id ?? '',
                    'name' => $lineup->player?->name ?? '',
                    'number' => $lineup->player?->jersey_number,
                    'position' => $lineup->position,
                    'team' => $lineup->club?->name ?? '',
                    'lineup_id' => $lineup->id ?? ''
                ];
            })
            ->values()
            ->all();
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