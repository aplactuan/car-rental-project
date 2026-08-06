<?php

namespace App\Http\Requests\Program;

use Illuminate\Foundation\Http\FormRequest;

class AddProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.uuid' => 'The selected customer must be a valid UUID.',
            'customer_id.exists' => 'The selected customer does not exist.',
        ];
    }
}
