<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLineupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            '*.match_id'   => ['required', 'exists:matches,id'],
            '*.club_id'    => ['required', 'exists:clubs,id'],
            '*.position'   => ['required', 'string'],
            '*.is_starter' => ['required', 'boolean'],
            '*.minute_in'  => ['nullable', 'integer'],
            '*.minute_out' => ['nullable', 'integer'],
            '*.lineup_id'  => ['nullable', 'string'],
        ];

        // Add unique match_id + player_id validation per row
        foreach ($this->input() as $index => $row) {
            $rules["{$index}.player_id"] = [
                'required',
                'exists:players,id',
                Rule::unique('lineups', 'player_id')
                    ->where('match_id', $row['match_id'] ?? null),
            ];
        }

        return $rules;
    }
}