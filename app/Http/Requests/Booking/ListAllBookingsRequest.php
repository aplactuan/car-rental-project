<?php

namespace App\Http\Requests\Booking;

use App\Support\BookingListFilters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAllBookingsRequest extends FormRequest
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
            'status' => ['sometimes', 'string', Rule::in(BookingListFilters::allowedDetailedStatuses())],
        ];
    }

    /**
     * @return array{status?: string}
     */
    public function filters(): array
    {
        return $this->safe()->only([BookingListFilters::PARAM_STATUS]);
    }
}
