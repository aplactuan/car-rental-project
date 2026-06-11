<?php

namespace App\Http\Requests\BillPayment;

use App\Enums\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddBillPaymentRequest extends FormRequest
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
            'amount' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $bill = $this->route('transaction') instanceof Transaction
                        ? $this->route('transaction')->bill
                        : null;

                    if ($bill !== null && $value > $bill->balance()) {
                        $fail('The amount must not exceed the bill\'s remaining balance of '.$bill->balance().'.');
                    }
                },
            ],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference_number' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'proof_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proof_image.nullable' => 'A proof of payment image is optional.',
            'proof_image.image' => 'The proof of payment must be an image.',
            'proof_image.max' => 'The proof of payment image must not exceed 10MB.',
        ];
    }
}
