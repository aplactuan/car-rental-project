<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AddTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bookings' => ['required', 'array', 'min:1'],
            'bookings.*.car_id' => ['required', 'string', 'exists:cars,id'],
            'bookings.*.driver_id' => ['required', 'string', 'exists:drivers,id'],
            'bookings.*.note' => ['nullable', 'string', 'max:65535'],
            'bookings.*.start_date' => ['required', 'date'],
            'bookings.*.end_date' => ['required', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $bookings = $this->input('bookings', []);
            foreach ($bookings as $index => $booking) {
                $start = $booking['start_date'] ?? null;
                $end = $booking['end_date'] ?? null;
                if ($start && $end && $end < $start) {
                    $validator->errors()->add(
                        "bookings.{$index}.end_date",
                        'The end date must be on or after the start date.'
                    );
                }
            }
        });
    }
}
