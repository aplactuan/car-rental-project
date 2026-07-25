<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'name' => ['required', 'string', 'max:255'],
            'po_number' => [
                'nullable',
                'string',
                'max:255',
                'unique:transactions,po_number,'.$this->route('transaction')->id,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'A customer is required for this transaction.',
            'customer_id.uuid' => 'The selected customer must be a valid UUID.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'name.required' => 'A name is required for this transaction.',
            'po_number.unique' => 'This PO number is already in use.',
        ];
    }
}
