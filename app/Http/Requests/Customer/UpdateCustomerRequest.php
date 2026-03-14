<?php

namespace App\Http\Requests\Customer;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
