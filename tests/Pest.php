<?php

use Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

if (! function_exists('carPayload')) {
    function carPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'SUV',
            'door' => 5,
            'seats' => 5,
            'year' => 2020,
            'color' => 'Black',
            'make' => 'Toyota',
            'model' => 'Raize',
            'plate_number' => 'IJC2912',
        ], $overrides);
    }
}

if (! function_exists('bookingPayload')) {
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

if (! function_exists('transactionPayload')) {
    function transactionPayload(array $bookingsOverrides = []): array
    {
        $bookings = $bookingsOverrides ?: [bookingPayload()];

        return [
            'bookings' => $bookings,
        ];
    }
}
