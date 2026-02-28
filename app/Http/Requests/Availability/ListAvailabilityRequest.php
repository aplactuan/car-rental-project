<?php

namespace App\Http\Requests\Availability;

use Illuminate\Foundation\Http\FormRequest;

class ListAvailabilityRequest extends FormRequest
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
            'type' => ['required', 'in:car,driver'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'The type field is required.',
            'type.in' => 'The type must be either car or driver.',
            'start.required' => 'The start field is required.',
            'start.date' => 'The start must be a valid date time.',
            'end.required' => 'The end field is required.',
            'end.date' => 'The end must be a valid date time.',
            'end.after' => 'The end must be a date time after start.',
        ];
    }
}
