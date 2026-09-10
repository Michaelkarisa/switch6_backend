<?php

namespace App\Services;

use App\Models\Lineup;
use App\Models\ShareLineup;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ShareLineupService
{

public function shareLineup(User $user, array $data){
    $sharedData =[
     'club_id' => $data['club_id']??'',
     'match_id' => $data['match_id']??'',
     'recepient_id' => $data['recepient_id']??'',
     'sender_id' => $user->id,
    ];
    $sharedLineup = ShareLineup::create($sharedData);

    return $sharedLineup;
}

public function fetchSharedLineup(User $user, array $data){

$lineups = Lineup::where('club_id', $data['club_id'])->where('match_id', $data['match_id'])->get();

return $lineups;

}


}