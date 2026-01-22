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
