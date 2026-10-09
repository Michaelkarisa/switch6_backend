<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'player_id'       => ['required', 'exists:players,id'],
            'club_id'         => ['required', 'exists:clubs,id'],
            'minute'          => ['required', 'integer'],
            'goal_type'       => ['nullable', Rule::in(['regular', 'penalty', 'own_goal', 'free_kick'])],
            'assist_player_id' => ['nullable', 'exists:players,id'],
            // No longer used by GoalService::record() — MatchModel's
            // getHomeScoreAttribute/getAwayScoreAttribute derive the score
            // by counting `scorers` rows instead of trusting client input.
            // Left as accepted-but-ignored (rather than removed outright)
            // so an existing caller that still sends them doesn't start
            // getting validation errors.
            'home_score'      => ['sometimes', 'integer'],
            'away_score'      => ['sometimes', 'integer'],
        ];
    }
}
