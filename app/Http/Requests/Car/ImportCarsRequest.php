<?php

namespace App\Http\Requests\Car;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportCarsRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'A CSV file is required.',
            'file.file' => 'The upload must be a file.',
            'file.mimes' => 'The file must be a CSV.',
            'file.max' => 'The file must not exceed 10MB.',
        ];
    }
}
