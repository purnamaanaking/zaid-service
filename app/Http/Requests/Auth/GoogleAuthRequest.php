<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class GoogleAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string'],
            'device' => ['nullable', 'array'],
            'device.platform' => ['nullable', 'string'],
            'device.device_id' => ['nullable', 'string'],
            'device.device_name' => ['nullable', 'string'],
        ];
    }
}
