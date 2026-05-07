<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scheduled_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'scheduled_time' => ['nullable', 'date_format:H:i:s'],
            'timezone' => ['nullable', 'string'],
            'all_day' => ['nullable', 'boolean'],
            'recurrence' => ['nullable', 'array'],
            'recurrence.type' => ['required_with:recurrence', 'string', 'in:daily,weekly,monthly'],
            'recurrence.day_of_week' => ['nullable', 'string'],
            'recurrence.interval' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
