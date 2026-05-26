<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait InteractsWithBillListQueryParameters
{
    private const BILL_STATUSES = ['draft', 'issued', 'paid', 'cancelled'];

    /**
     * @return array<string, mixed>
     */
    protected function billListQueryRules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', Rule::in(['issued_at', '-issued_at', 'created_at', '-created_at'])],
            'filter' => ['sometimes', 'array'],
            'filter.status' => ['sometimes', 'string', $this->billListStatusRule()],
            'filter.invoice_number' => ['sometimes', 'string'],
            'filter.issued_at' => ['sometimes', 'array'],
            'filter.issued_at.from' => ['sometimes', 'nullable', 'date'],
            'filter.issued_at.to' => ['sometimes', 'nullable', 'date', $this->billListIssuedAtToRule()],
            'include' => ['sometimes', 'string', $this->billListIncludeRule()],
        ];
    }

    /**
     * @return \Closure(string, mixed, \Closure(string): void): void
     */
    private function billListIssuedAtToRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) && ! is_numeric($value)) {
                return;
            }

            $from = $this->input('filter.issued_at.from');
            if ($from === null || $from === '') {
                return;
            }

            if (strtotime((string) $value) < strtotime((string) $from)) {
                $fail('The filter issued at to date must be on or after the from date.');
            }
        };
    }

    /**
     * @return \Closure(string, mixed, \Closure(string): void): void
     */
    private function billListIncludeRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            foreach (array_filter(array_map('trim', explode(',', $value))) as $token) {
                if ($token !== 'transaction') {
                    $fail('The include field may only contain transaction.');
                }
            }
        };
    }

    /**
     * @return \Closure(string, mixed, \Closure(string): void): void
     */
    private function billListStatusRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            foreach (array_filter(array_map('trim', explode(',', $value))) as $status) {
                if (! in_array($status, self::BILL_STATUSES, true)) {
                    $fail('One or more values in filter.status are not valid bill statuses.');
                }
            }
        };
    }

    /**
     * @return array{
     *     status?: array<int, string>,
     *     invoice_number?: string,
     *     issued_at_from?: string,
     *     issued_at_to?: string,
     *     sort?: string
     * }
     */
    public function billListFilters(): array
    {
        $filters = [];
        $filterInput = $this->input('filter', []);

        if (isset($filterInput['status']) && is_string($filterInput['status']) && $filterInput['status'] !== '') {
            $filters['status'] = array_values(array_unique(array_filter(array_map(
                'trim',
                explode(',', $filterInput['status'])
            ))));
        }

        if (isset($filterInput['invoice_number']) && is_string($filterInput['invoice_number'])) {
            $invoiceNumber = trim($filterInput['invoice_number']);

            if ($invoiceNumber !== '') {
                $filters['invoice_number'] = $invoiceNumber;
            }
        }

        if (isset($filterInput['issued_at']['from']) && $filterInput['issued_at']['from'] !== null && $filterInput['issued_at']['from'] !== '') {
            $filters['issued_at_from'] = (string) $filterInput['issued_at']['from'];
        }

        if (isset($filterInput['issued_at']['to']) && $filterInput['issued_at']['to'] !== null && $filterInput['issued_at']['to'] !== '') {
            $filters['issued_at_to'] = (string) $filterInput['issued_at']['to'];
        }

        if ($this->filled('sort')) {
            $filters['sort'] = (string) $this->input('sort');
        }

        return $filters;
    }

    public function shouldIncludeTransaction(): bool
    {
        if (! $this->filled('include')) {
            return false;
        }

        $tokens = array_map('trim', explode(',', (string) $this->input('include')));

        return in_array('transaction', $tokens, true);
    }
}
