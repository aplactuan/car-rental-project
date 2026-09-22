<?php

namespace App\Http\Requests\Audit;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAuditsRequest extends FormRequest
{
    /** @var array<string, class-string> */
    public const AUDITABLE_TYPES = [
        'trip-report' => TripReport::class,
        'purchase-order' => PurchaseOrder::class,
        'invoice' => Invoice::class,
    ];

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
            'customer_id' => ['sometimes', 'uuid', 'exists:customers,id'],
            'auditable_type' => ['sometimes', Rule::in(array_keys(self::AUDITABLE_TYPES))],
            'auditable_id' => ['sometimes', 'uuid'],
            'event' => ['sometimes', Rule::in(['created', 'updated', 'deleted'])],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
        ];
    }

    /**
     * @return array{customer_id?: string, auditable_type?: class-string, auditable_id?: string, event?: string, user_id?: int, date_from?: string, date_to?: string}
     */
    public function filters(): array
    {
        $filters = [];

        foreach (['customer_id', 'auditable_id', 'event', 'date_from', 'date_to'] as $filter) {
            if ($this->filled($filter)) {
                $filters[$filter] = $this->string($filter)->toString();
            }
        }

        if ($this->filled('auditable_type')) {
            $filters['auditable_type'] = self::AUDITABLE_TYPES[$this->string('auditable_type')->toString()];
        }

        if ($this->filled('user_id')) {
            $filters['user_id'] = $this->integer('user_id');
        }

        return $filters;
    }
}
