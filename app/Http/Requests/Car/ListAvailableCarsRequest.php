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
            'type' => ['sometimes', 'string', 'max:100'],
            'door' => ['sometimes', 'integer', 'min:1'],
            'seats' => ['sometimes', 'integer', 'min:1'],
            'year' => ['sometimes', 'integer'],
            'color' => ['sometimes', 'string', 'max:100'],
            'make' => ['sometimes', 'string', 'max:100'],
            'model' => ['sometimes', 'string', 'max:100'],
            'plate_number' => ['sometimes', 'string', 'max:100'],
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
            'door.integer' => 'The door must be a number.',
            'door.min' => 'The door must be at least 1.',
            'seats.integer' => 'The seats must be a number.',
            'seats.min' => 'The seats must be at least 1.',
        ];
    }
}
