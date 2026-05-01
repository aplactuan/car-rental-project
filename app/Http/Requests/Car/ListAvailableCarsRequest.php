<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;

class ListAvailableCarsRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'make' => ['sometimes', 'string', 'max:100'],
            'model' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', 'string', 'max:100'],
            'number_of_seats' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'The per_page must be a number.',
            'per_page.min' => 'The per_page must be at least 1.',
            'per_page.max' => 'The per_page must not be greater than 100.',
            'number_of_seats.integer' => 'The number_of_seats must be a number.',
            'number_of_seats.min' => 'The number_of_seats must be at least 1.',
        ];
    }
}
