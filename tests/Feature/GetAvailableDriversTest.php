<?php

use App\Models\Driver;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot list drivers when not authenticated', function () {
        getJson('/api/v1/drivers')->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('with start_date and end_date returns only drivers available in that period', function () {
        $user = User::factory()->create();
        $driverAvailable = Driver::factory()->create(['license_number' => 'AVAIL-001']);
        $driverBooked = Driver::factory()->create(['license_number' => 'BOOKED-001']);
        $car = \App\Models\Car::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);
        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driverBooked->id,
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-15',
            'note' => null,
        ]);

        $response = getJson('/api/v1/drivers?start_date=2026-02-10&end_date=2026-02-15');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        expect($ids)->not->toContain($driverBooked->id);
        expect($ids)->toContain($driverAvailable->id);
    });

    test('with start_date and end_date driver booked in period is available outside period', function () {
        $user = User::factory()->create();
        $driver = Driver::factory()->create(['license_number' => 'ONE-DRV']);
        $car = \App\Models\Car::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);
        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-15',
            'note' => null,
        ]);

        $response = getJson('/api/v1/drivers?start_date=2026-02-20&end_date=2026-02-25');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        expect($ids)->toContain($driver->id);
    });
});
