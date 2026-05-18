<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMatchViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'match_id' => ['required', 'exists:matches,id'],
            'user_id'  => ['nullable', 'exists:users,id'],
            'minute'   => ['nullable', 'integer'],
        ];
    }
}
