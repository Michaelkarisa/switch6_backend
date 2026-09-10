<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchStoreMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*.home_club_id'         => ['required', 'exists:clubs,id'],
            '*.away_club_id'         => ['required', 'exists:clubs,id'],
            '*.match_date'           => ['required', 'date'],
            '*.home_score'           => ['nullable', 'integer'],
            '*.away_score'           => ['nullable', 'integer'],
            '*.status'               => ['nullable', 'string'],
            '*.venue'                => ['nullable', 'string'],
            '*.referee'              => ['nullable', 'string'],
            '*.highlights'           => ['nullable', 'string'],
            '*.url'                  => ['nullable', 'string'],
            '*.away_formation'       => ['nullable', 'string'],
            '*.home_formation'       => ['nullable', 'string'],
            '*.cancellation_reason'  => ['nullable', 'string'],
            '*.league_id'            => ['nullable', 'exists:leagues,id'],
            '*.author_id'            => ['nullable', 'exists:users,id'],
        ];
    }
}
