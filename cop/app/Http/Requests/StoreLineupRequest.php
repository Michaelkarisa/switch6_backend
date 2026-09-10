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
            '*.player_id'  => ['required', 'exists:players,id'], // Base validation; unique rule added dynamically below
        ];
        return $rules;
    }
}