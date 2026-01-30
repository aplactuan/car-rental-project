<?php

uses(Tests\TestCase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Unit');

if (!function_exists('carPayload')) {
    function carPayload(array $overrides = []): array
    {
        return array_merge([
            'make' => 'Toyota',
            'model' => 'Raize',
            'year' => 2020,
            'mileage' => 5000,
            'type' => 'SUV',
            'number_of_seats' => 5,
            'plate_number' => 'IJC2912',
        ], $overrides);
    }
}

if (!function_exists('bookingPayload')) {
    function bookingPayload(array $overrides = []): array
    {
        return array_merge([
            'car_id' => null,
            'driver_id' => null,
            'note' => 'Test booking note',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(3)->format('Y-m-d'),
        ], $overrides);
    }
}

if (!function_exists('transactionPayload')) {
    function transactionPayload(array $bookingsOverrides = []): array
    {
        $bookings = $bookingsOverrides ?: [bookingPayload()];

        return [
            'bookings' => $bookings,
        ];
    }
}
