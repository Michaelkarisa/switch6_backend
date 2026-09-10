<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStreamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'match_id'             => ['required', 'string', 'max:255', 'unique:stream_sessions,match_id'],
            'status'               => ['sometimes', 'string', 'max:50'],
            'broadcaster_id'       => ['sometimes', 'nullable', 'integer'],
            'current_streamer'     => ['sometimes', 'nullable', 'ip'],
            'platform_targets'     => ['sometimes', 'array'],
            'members'              => ['sometimes', 'array'],
            'match_period'         => ['sometimes', 'string', 'max:255'],
            'other_match_id'       => ['sometimes', 'string', 'max:255']
        ];
    }
}
