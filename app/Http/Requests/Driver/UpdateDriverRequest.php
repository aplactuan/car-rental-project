<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRequest extends FormRequest
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
        return [
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'license_number' => 'sometimes|string|unique:drivers,license_number,' . $this->route('driver')->id,
            'license_expiry_date' => 'sometimes|date',
            'address' => 'sometimes|string',
            'phone_number' => 'sometimes|string',
        ];
    }
}
