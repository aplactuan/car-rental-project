<?php

namespace App\Http\Requests\Customer;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string',
            'type' => 'sometimes|string|in:'.Customer::TYPE_PERSONAL.','.Customer::TYPE_BUSINESS,
            'parent_id' => ['sometimes', 'nullable', 'uuid', 'exists:customers,id'],
            'contact_person' => ['sometimes', 'nullable', 'string'],
            'contact_mobile_number' => ['sometimes', 'nullable', 'string'],
            'contact_email' => ['sometimes', 'nullable', 'email'],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->has('parent_id')) {
                return;
            }

            /** @var Customer $customer */
            $customer = $this->route('customer');
            $parentId = $this->input('parent_id');

            if ($parentId === null) {
                return;
            }

            if ($parentId === $customer->id) {
                $validator->errors()->add('parent_id', 'A customer cannot be its own parent.');

                return;
            }

            if ($customer->hasDescendant($parentId)) {
                $validator->errors()->add('parent_id', 'A customer cannot have one of its sub-accounts as a parent.');
            }
        });
    }
}
