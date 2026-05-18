<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:100'],
            'club_id'      => ['required', 'exists:clubs,id'],
            'position'     => ['required', 'string'],
            'age'          => ['nullable', 'integer'],
            'nationality'  => ['nullable', 'string'],
            'jersey_number' => ['nullable', 'integer'],
            'market_value' => ['nullable', 'numeric'],
        ];
    }
}
