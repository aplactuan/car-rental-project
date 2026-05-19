<?php

namespace App\Http\Requests\Car;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddCarRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string',
            'door' => 'required|integer',
            'seats' => 'required|integer',
            'year' => 'required|integer',
            'color' => 'required|string',
            'make' => 'required|string',
            'model' => 'required|string',
            'plate_number' => 'required|string',
        ];
    }
}
