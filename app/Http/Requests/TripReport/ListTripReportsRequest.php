<?php

namespace App\Http\Requests\TripReport;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListTripReportsRequest extends FormRequest
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
        return [];
    }
}
