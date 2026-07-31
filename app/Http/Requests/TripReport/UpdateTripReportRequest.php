<?php

namespace App\Http\Requests\TripReport;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTripReportRequest extends FormRequest
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
            'report_date' => ['sometimes', 'required', 'date'],
            'trip_start' => ['sometimes', 'required', 'date'],
            'trip_end' => ['sometimes', 'required', 'date'],
            'driver' => ['sometimes', 'required', 'string', 'max:255'],
            'destinations' => ['sometimes', 'required', 'string'],
            'amount' => ['sometimes', 'required', 'integer', 'min:0'],
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
