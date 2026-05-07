<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'verification_id' => ['required', 'uuid'],
            'otp_code' => ['required', 'string', 'min:4', 'max:10'],
        ];
    }
}
