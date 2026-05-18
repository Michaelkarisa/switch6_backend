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
            'home_score'      => ['required', 'integer'],
            'away_score'      => ['required', 'integer'],
        ];
    }
}
