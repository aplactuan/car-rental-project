<?php

namespace App\Http\Requests\TripReport;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AddTripReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $booking = $this->route('booking');

        if (! $user instanceof User || ! $booking instanceof Booking) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->driver?->id === $booking->driver_id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_date' => ['required', 'date'],
            'po_number' => ['nullable', 'string', 'max:255'],
            'time_in' => ['nullable', 'date_format:H:i'],
            'time_out' => ['nullable', 'date_format:H:i'],
            'rate' => ['nullable', 'integer', 'min:0'],
            'odometer_in' => ['nullable', 'integer', 'min:0'],
            'odometer_out' => ['nullable', 'integer', 'min:0'],
            'fuel_liters' => ['nullable', 'numeric', 'min:0'],
            'fuel_amount' => ['nullable', 'integer', 'min:0'],
            'invoice_or_or_number' => ['nullable', 'string', 'max:255'],
            'collection_amount' => ['nullable', 'integer', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'destinations' => ['nullable', 'array', 'max:6'],
            'destinations.*' => ['array:from,to'],
            'destinations.*.from' => ['nullable', 'string', 'max:255'],
            'destinations.*.to' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $timeIn = $this->input('time_in');
            $timeOut = $this->input('time_out');

            if (
                $timeIn &&
                $timeOut &&
                ! $validator->errors()->hasAny(['time_in', 'time_out']) &&
                Carbon::createFromFormat('H:i', $timeOut)->lt(Carbon::createFromFormat('H:i', $timeIn))
            ) {
                $validator->errors()->add('time_out', 'The time out field must be a time after or equal to time in.');
            }

            $odometerIn = $this->input('odometer_in');
            $odometerOut = $this->input('odometer_out');

            if ($odometerIn !== null && $odometerOut !== null && (int) $odometerOut < (int) $odometerIn) {
                $validator->errors()->add('odometer_out', 'The odometer out field must be greater than or equal to odometer in.');
            }
        });
    }
}
