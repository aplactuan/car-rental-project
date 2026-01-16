<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $carId = $this->route('car');

        return [
            'make' => 'sometimes|string',
            'model' => 'sometimes|string',
            'plate_number' => [
                'sometimes',
                'string',
                Rule::unique('cars', 'plate_number')->ignore($carId),
            ],
            'mileage' => 'sometimes|integer',
            'type' => 'sometimes|string',
            'number_of_seats' => 'sometimes|integer',
            'year' => 'sometimes|integer',
        ];
    }
}
