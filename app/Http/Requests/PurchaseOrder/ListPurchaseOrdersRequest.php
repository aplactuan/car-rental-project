<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ListPurchaseOrdersRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'customer_id' => ['sometimes', 'nullable', 'uuid', 'exists:customers,id'],
            'program_id' => ['sometimes', 'nullable', 'uuid', 'exists:programs,id'],
            'unprogrammed' => ['sometimes', 'boolean'],
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
            'program_id.uuid' => 'The selected program must be a valid UUID.',
            'program_id.exists' => 'The selected program does not exist.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->boolean('unprogrammed') && $this->filled('program_id')) {
                $validator->errors()->add(
                    'unprogrammed',
                    'Cannot use unprogrammed together with program_id.'
                );
            }
        });
    }

    /**
     * @return array{customer_id?: string, program_id?: string, unprogrammed?: true}
     */
    public function filters(): array
    {
        $filters = [];

        if ($this->filled('customer_id')) {
            $filters['customer_id'] = $this->string('customer_id')->toString();
        }

        if ($this->boolean('unprogrammed')) {
            $filters['unprogrammed'] = true;
        } elseif ($this->filled('program_id')) {
            $filters['program_id'] = $this->string('program_id')->toString();
        }

        return $filters;
    }
}
