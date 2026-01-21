<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class AddDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'license_number' => 'required|string|unique:drivers,license_number',
            'license_expiry_date' => 'required|date',
            'address' => 'required|string',
            'phone_number' => 'required|string',
        ];
    }
}

