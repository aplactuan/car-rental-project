<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddUserRequest extends FormRequest
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
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['sometimes', Rule::enum(UserRole::class)->only([UserRole::User])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('role')) {
            $this->merge([
                'role' => UserRole::User->value,
            ]);
        }
    }
}
