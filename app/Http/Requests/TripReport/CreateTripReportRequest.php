<?php

namespace App\Http\Requests\TripReport;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateTripReportRequest extends FormRequest
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
            'report_date' => ['required', 'date'],
            'driver' => ['required', 'string', 'max:255'],
            'destinations' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min:0'],
            'trip_report_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trip_report_image.image' => 'The trip report image must be an image.',
            'trip_report_image.max' => 'The trip report image must not exceed 10MB.',
        ];
    }
}
