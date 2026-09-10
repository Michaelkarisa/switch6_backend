<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\Event;


class AdTargetingService
{
    public function pickBestAd(string $matchId, string $period)
    {
        return Advertisement::where('status', 'active')
            ->where(function ($q) use ($period) {
                $q->where('period', $period)->orWhereNull('period');
            })
            ->get()
            ->sortByDesc(function ($ad) use ($matchId) {
                return $ad->events()
                    ->where('match_id', $matchId)
                    ->count();
            })
            ->first();
    }

    public function getads(string $matchId, string $period){
        //get ads specific tied to that match. that is an advertiser requested to specific advertice to that match. remember an ad has an array of match_ids
        //get ads that are general or random targeted will use criteria to randomly advertise them. will use target_tags from advertisement. use location too, match stadiums.
        //will priotise ad slots for the specific tied ads then the random ones. slot 1 and 2 will be for specified and slot 3 and 4 will be for the general ads.
        // periods like halftime and fulltime will have 10 ad slots, ie broadcaster will be forced to go a break of 10 minutes before they are allowed to resume the session.
         return Advertisement::where('status', 'active')
            ->where(function ($q) use ($period, $matchId){
                $q->where('period', $period)->orWhereIn('match_ids',$matchId);
            })
            ->get();
    }
}