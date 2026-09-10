<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStreamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'               => ['sometimes', 'string', 'max:50'],
            'current_streamer'     => ['sometimes', 'nullable', 'ip'],
            'platform_targets'     => ['sometimes', 'array'],
            'members'              => ['sometimes', 'array'],
        ];
    }
}
