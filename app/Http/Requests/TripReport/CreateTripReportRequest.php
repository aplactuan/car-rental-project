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
            'trip_start' => ['required', 'date'],
            'trip_end' => ['required', 'date'],
            'driver' => ['required', 'string', 'max:255'],
            'destinations' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min:0'],
            'trip_report_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trip_report_image.mimes' => 'The trip report file must be an image, document, or PDF.',
            'trip_report_image.max' => 'The trip report file must not exceed 10MB.',
        ];
    }
}
