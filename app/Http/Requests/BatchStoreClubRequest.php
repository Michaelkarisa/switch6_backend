<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchStoreClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*.name'         => ['required', 'string', 'max:100'],
            '*.city'         => ['nullable', 'string'],
            '*.founded_year' => ['nullable', 'integer'],
            '*.stadium'      => ['nullable', 'string'],
            '*.manager'      => ['nullable', 'string'],
            '*.jersey_color' => ['nullable', 'integer'],
            '*.logo_url'     => ['nullable', 'string'],
            '*.logo'         => ['nullable', 'string'],
        ];
    }
}
