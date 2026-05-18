<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'home_club_id' => [
                'sometimes',
                'exists:clubs,id',
                'different:away_club_id',
            ],

            'away_club_id' => [
                'sometimes',
                'exists:clubs,id',
                'different:home_club_id',
            ],

            'match_date' => [
                'sometimes',
                'date',
            ],

            'venue' => [
                'nullable',
                'string',
                'max:255',
            ],

            'referee_id' => [
                'nullable',
                'exists:referees,id',
            ],

            'home_formation' => [
                'nullable',
                'string',
                'max:50',
            ],

            'away_formation' => [
                'nullable',
                'string',
                'max:50',
            ],

            'author_id' => [
                'nullable',
                'exists:users,id',
            ],

            'league_id' => [
                'nullable',
                'exists:leagues,id',
            ],
        ];
    }
}