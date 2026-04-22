<?php

namespace App\Http\Requests\Bill;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBillRequest extends FormRequest
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
            'status' => ['sometimes', 'in:issued,paid,cancelled'],
            'amount' => ['sometimes', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
