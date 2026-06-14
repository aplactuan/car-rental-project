<?php

namespace App\Http\Requests\Driver;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $driver = $this->route('driver');

        if ($user->isAdmin()) {
            return true;
        }

        return $user->driver?->is($driver) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'license_number' => 'sometimes|string|unique:drivers,license_number,'.$this->route('driver')->id,
            'license_expiry_date' => 'sometimes|date',
            'address' => 'sometimes|string',
            'phone_number' => 'sometimes|string',
        ];

        if ($this->user()?->isAdmin()) {
            $rules['user_id'] = 'nullable|integer|exists:users,id|unique:drivers,user_id,'.$this->route('driver')->id;
        }

        return $rules;
    }
}
