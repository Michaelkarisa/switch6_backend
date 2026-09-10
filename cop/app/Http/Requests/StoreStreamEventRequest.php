<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStreamEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:stream_sessions,id'],
            'type'       => ['required', 'string', 'max:100'],
            'payload'    => ['sometimes', 'array'],
        ];
    }
}
