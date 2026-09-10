<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'string', 'max:100'],
            'club_id'      => ['sometimes', 'exists:clubs,id'],
            'position'     => ['sometimes', 'string'],
            'age'          => ['nullable', 'integer'],
            'nationality'  => ['nullable', 'string'],
            'jersey_number' => ['nullable', 'integer'],
            'market_value' => ['nullable', 'numeric'],
        ];
    }
}