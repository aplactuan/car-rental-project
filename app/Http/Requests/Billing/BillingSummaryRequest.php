<?php

namespace App\Http\Requests\Billing;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BillingSummaryRequest extends FormRequest
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
            'filter' => ['sometimes', 'array'],
            'filter.customer_id' => ['sometimes', 'nullable', 'uuid', 'exists:customers,id'],
            'filter.paid_at' => ['sometimes', 'array'],
            'filter.paid_at.from' => ['sometimes', 'nullable', 'date'],
            'filter.paid_at.to' => ['sometimes', 'nullable', 'date', $this->paidAtToRule()],
            'filter.as_of' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return \Closure(string, mixed, \Closure(string): void): void
     */
    private function paidAtToRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) && ! is_numeric($value)) {
                return;
            }

            $from = $this->input('filter.paid_at.from');
            if ($from === null || $from === '') {
                return;
            }

            if (strtotime((string) $value) < strtotime((string) $from)) {
                $fail('The filter paid at to date must be on or after the from date.');
            }
        };
    }

    /**
     * @return array{
     *     customer_id?: string,
     *     paid_at_from?: string,
     *     paid_at_to?: string,
     *     as_of?: string
     * }
     */
    public function filters(): array
    {
        $filters = [];
        $filterInput = $this->input('filter', []);

        if (isset($filterInput['customer_id']) && is_string($filterInput['customer_id']) && $filterInput['customer_id'] !== '') {
            $filters['customer_id'] = $filterInput['customer_id'];
        }

        if (isset($filterInput['paid_at']['from']) && $filterInput['paid_at']['from'] !== null && $filterInput['paid_at']['from'] !== '') {
            $filters['paid_at_from'] = (string) $filterInput['paid_at']['from'];
        }

        if (isset($filterInput['paid_at']['to']) && $filterInput['paid_at']['to'] !== null && $filterInput['paid_at']['to'] !== '') {
            $filters['paid_at_to'] = (string) $filterInput['paid_at']['to'];
        }

        if (isset($filterInput['as_of']) && $filterInput['as_of'] !== null && $filterInput['as_of'] !== '') {
            $filters['as_of'] = (string) $filterInput['as_of'];
        }

        return $filters;
    }
}
