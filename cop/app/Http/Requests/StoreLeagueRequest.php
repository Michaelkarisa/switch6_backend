<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeagueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leaguename' => ['required', 'string', 'max:100'],
            'name'       => ['nullable', 'string'],
            'type'       => ['required', 'string'],
            'logo'       => ['nullable', 'string'],
        ];
    }
}
