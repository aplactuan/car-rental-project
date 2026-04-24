<?php

namespace App\Http\Requests\Booking;

use App\Support\BookingListFilters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListBookingsRequest extends FormRequest
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
            'per_page' => 'sometimes|integer|min:1|max:100',
            'status' => ['sometimes', 'string', Rule::in(BookingListFilters::allowedStatuses())],
            'period' => ['sometimes', 'string', Rule::in(BookingListFilters::allowedPeriods())],
            'car_id' => ['sometimes', 'uuid', 'exists:cars,id'],
            'driver_id' => ['sometimes', 'uuid', 'exists:drivers,id'],
        ];
    }

    /**
     * @return array{
     *     status?: string,
     *     period?: string,
     *     car_id?: string,
     *     driver_id?: string
     * }
     */
    public function filters(): array
    {
        return $this->safe()->only(self::supportedFilterParameters());
    }

    /**
     * @return array<int, string>
     */
    public static function supportedFilterParameters(): array
    {
        return [
            BookingListFilters::PARAM_STATUS,
            BookingListFilters::PARAM_PERIOD,
            BookingListFilters::PARAM_CAR_ID,
            BookingListFilters::PARAM_DRIVER_ID,
        ];
    }
}
