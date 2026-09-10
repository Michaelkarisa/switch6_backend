<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AwardBroadcasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_views' => ['required', 'integer', 'min:0'],
        ];
    }
}
