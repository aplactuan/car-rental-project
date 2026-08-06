<?php

namespace App\Http\Requests\Program;

use Illuminate\Foundation\Http\FormRequest;

class ListProgramsRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'customer_id' => ['sometimes', 'nullable', 'uuid', 'exists:customers,id'],
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

    /**
     * @return array{customer_id?: string}
     */
    public function filters(): array
    {
        $filters = [];

        if ($this->filled('customer_id')) {
            $filters['customer_id'] = $this->string('customer_id')->toString();
        }

        return $filters;
    }
}
