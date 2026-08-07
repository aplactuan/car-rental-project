<?php

namespace App\Http\Requests\TripReport;

use App\Support\Media\MediaUploader;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $purchaseOrderId = $this->route('purchaseOrder')->id;
        $tripReportId = $this->route('tripReport')->id;

        return [
            'trip_report_no' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('trip_reports', 'trip_report_no')
                    ->where('purchase_order_id', $purchaseOrderId)
                    ->ignore($tripReportId),
            ],
            'report_date' => ['sometimes', 'required', 'date'],
            'trip_start' => ['sometimes', 'required', 'date'],
            'trip_end' => ['sometimes', 'required', 'date'],
            'driver' => ['sometimes', 'required', 'string', 'max:255'],
            'destinations' => ['sometimes', 'required', 'string'],
            'amount' => ['sometimes', 'required', 'integer', 'min:0'],
            'trip_report_image' => ['nullable', 'file', 'mimes:'.MediaUploader::IMAGE_DOCUMENT_OR_PDF_MIMES, 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trip_report_no.unique' => 'This trip report number is already used for this purchase order.',
            'trip_report_image.mimes' => 'The trip report file must be an image, document, or PDF.',
            'trip_report_image.max' => 'The trip report file must not exceed 10MB.',
        ];
    }
}
