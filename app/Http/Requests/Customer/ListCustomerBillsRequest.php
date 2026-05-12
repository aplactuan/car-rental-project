<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\Concerns\InteractsWithBillListQueryParameters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListCustomerBillsRequest extends FormRequest
{
    use InteractsWithBillListQueryParameters;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->billListQueryRules();
    }

    /**
     * @return array{
     *     status?: array<int, string>,
     *     issued_at_from?: string,
     *     issued_at_to?: string,
     *     sort?: string
     * }
     */
    public function filters(): array
    {
        return $this->billListFilters();
    }
}
