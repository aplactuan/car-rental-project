<?php

namespace App\Http\Requests\Customer;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class AddCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'type' => 'required|string|in:'.Customer::TYPE_PERSONAL.','.Customer::TYPE_BUSINESS,
            'parent_id' => ['sometimes', 'nullable', 'uuid', 'exists:customers,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_id.uuid' => 'The selected parent must be a valid UUID.',
            'parent_id.exists' => 'The selected parent account does not exist.',
        ];
    }
}
