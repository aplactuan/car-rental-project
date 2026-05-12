<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListTransactionsRequest extends FormRequest
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
            'has_bill' => ['sometimes', 'nullable', 'in:0,1,true,false'],
        ];
    }

    /**
     * @return array{has_bill?: bool}
     */
    public function filters(): array
    {
        $filters = [];

        if ($this->has('has_bill')) {
            $filters['has_bill'] = $this->boolean('has_bill');
        }

        return $filters;
    }
}
