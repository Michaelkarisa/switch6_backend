<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'match_id'  => ['required', 'integer', 'exists:matches,id'],
            'platform'    => ['required', 'string', 'max:100'],
            'view_count'  => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
