<?php

use App\Models\Car;
use App\Models\Driver;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot create a transaction when not authenticated', function () {
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $payload = transactionPayload([
            bookingPayload([
                'car_id' => $car->id,
                'driver_id' => $driver->id,
            ]),
        ]);

        postJson('/api/v1/transactions', $payload)->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can create a transaction with one booking', function () {
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $payload = transactionPayload([
            bookingPayload([
                'car_id' => $car->id,
                'driver_id' => $driver->id,
            ]),
        ]);

        $response = postJson('/api/v1/transactions', $payload);

        $response->assertStatus(201);
        $json = $response->json();
        expect($json)->toHaveKey('data');
        $data = $json['data'];
        expect($data)->toHaveKeys(['type', 'id', 'attributes', 'relationships']);
        expect($data['type'])->toBe('transaction');
        expect($data['attributes']['userId'])->toBe($this->user->id);
        expect($data['relationships']['bookings']['data'])->toBeArray();
        expect($data['relationships']['bookings']['data'][0])->toHaveKeys(['type', 'id']);
        expect($json)->toHaveKey('included');
        expect($json['included'][0])->toHaveKeys(['type', 'id', 'attributes']);
        expect($json['included'][0]['attributes'])->toHaveKeys(['note', 'startDate', 'endDate']);

        $this->assertDatabaseHas('transactions', ['user_id' => $this->user->id]);
        $this->assertDatabaseCount('bookings', 1);
    });

    test('can create a transaction with multiple bookings', function () {
        $car1 = Car::factory()->create();
        $car2 = Car::factory()->create();
        $driver1 = Driver::factory()->create();
        $driver2 = Driver::factory()->create();
        $payload = transactionPayload([
            bookingPayload([
                'car_id' => $car1->id,
                'driver_id' => $driver1->id,
                'start_date' => now()->addDays(1)->format('Y-m-d'),
                'end_date' => now()->addDays(3)->format('Y-m-d'),
            ]),
            bookingPayload([
                'car_id' => $car2->id,
                'driver_id' => $driver2->id,
                'start_date' => now()->addDays(5)->format('Y-m-d'),
                'end_date' => now()->addDays(7)->format('Y-m-d'),
            ]),
        ]);

        $response = postJson('/api/v1/transactions', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseCount('bookings', 2);
    });

    test('returns 422 when car_id is missing', function () {
        $driver = Driver::factory()->create();
        $payload = transactionPayload([
            bookingPayload([
                'driver_id' => $driver->id,
            ]),
        ]);
        unset($payload['bookings'][0]['car_id']);

        postJson('/api/v1/transactions', $payload)->assertStatus(422);
    });

    test('returns 422 when driver_id is missing', function () {
        $car = Car::factory()->create();
        $payload = transactionPayload([
            bookingPayload([
                'car_id' => $car->id,
            ]),
        ]);
        unset($payload['bookings'][0]['driver_id']);

        postJson('/api/v1/transactions', $payload)->assertStatus(422);
    });

    test('returns 422 when end_date is before start_date', function () {
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $payload = transactionPayload([
            bookingPayload([
                'car_id' => $car->id,
                'driver_id' => $driver->id,
                'start_date' => now()->addDays(5)->format('Y-m-d'),
                'end_date' => now()->addDays(2)->format('Y-m-d'),
            ]),
        ]);

        postJson('/api/v1/transactions', $payload)->assertStatus(422);
    });

    test('returns 422 when car is already booked in the period', function () {
        $car = Car::factory()->create();
        $driver1 = Driver::factory()->create();
        $driver2 = Driver::factory()->create();
        $start = now()->addDays(1)->format('Y-m-d');
        $end = now()->addDays(3)->format('Y-m-d');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver1->id,
            'start_date' => $start,
            'end_date' => $end,
            'note' => null,
        ]);

        $payload = transactionPayload([
            bookingPayload([
                'car_id' => $car->id,
                'driver_id' => $driver2->id,
                'start_date' => $start,
                'end_date' => $end,
            ]),
        ]);

        postJson('/api/v1/transactions', $payload)->assertStatus(422)
            ->assertJsonPath('errors.0.detail', 'The selected car is not available for the given dates.');
    });

    test('returns 422 when driver is already booked in the period', function () {
        $car1 = Car::factory()->create();
        $car2 = Car::factory()->create();
        $driver = Driver::factory()->create();
        $start = now()->addDays(1)->format('Y-m-d');
        $end = now()->addDays(3)->format('Y-m-d');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $transaction->bookings()->create([
            'car_id' => $car1->id,
            'driver_id' => $driver->id,
            'start_date' => $start,
            'end_date' => $end,
            'note' => null,
        ]);

        $payload = transactionPayload([
            bookingPayload([
                'car_id' => $car2->id,
                'driver_id' => $driver->id,
                'start_date' => $start,
                'end_date' => $end,
            ]),
        ]);

        postJson('/api/v1/transactions', $payload)->assertStatus(422)
            ->assertJsonPath('errors.0.detail', 'The selected driver is not available for the given dates.');
    });
});
