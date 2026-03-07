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
    test('cannot add a booking when not authenticated', function () {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $payload = bookingPayload([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
        ]);

        postJson("/api/v1/transactions/{$transaction->id}/book", $payload)->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can add a booking when car and driver are available', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $payload = bookingPayload([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-20',
            'end_date' => '2026-02-25',
        ]);

        $response = postJson("/api/v1/transactions/{$transaction->id}/book", $payload);

        $response->assertStatus(201);
        $json = $response->json();
        expect($json)->toHaveKey('data');
        $data = $json['data'];
        expect($data['type'])->toBe('booking');
        expect($data['attributes']['startDate'])->toBe('2026-02-20');
        expect($data['attributes']['endDate'])->toBe('2026-02-25');
        expect($data['relationships']['car']['data']['id'])->toBe($car->id);
        expect($data['relationships']['driver']['data']['id'])->toBe($driver->id);

        $this->assertDatabaseHas('bookings', [
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
        ]);
        $booking = $transaction->bookings()->first();
        expect($booking->start_date->format('Y-m-d'))->toBe('2026-02-20');
        expect($booking->end_date->format('Y-m-d'))->toBe('2026-02-25');
    });

    test('returns 422 when car is scheduled for the given dates', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $car->schedules()->create([
            'start_time' => '2026-02-10 00:00:00',
            'end_time' => '2026-02-15 23:59:59',
        ]);

        $payload = bookingPayload([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-12',
            'end_date' => '2026-02-14',
        ]);

        $response = postJson("/api/v1/transactions/{$transaction->id}/book", $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected car is not available for the given dates.',
        ]);
        $this->assertDatabaseCount('bookings', 0);
    });

    test('returns 422 when driver is scheduled for the given dates', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $driver->schedules()->create([
            'start_time' => '2026-02-10 00:00:00',
            'end_time' => '2026-02-15 23:59:59',
        ]);

        $payload = bookingPayload([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-12',
            'end_date' => '2026-02-14',
        ]);

        $response = postJson("/api/v1/transactions/{$transaction->id}/book", $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected driver is not available for the given dates.',
        ]);
        $this->assertDatabaseCount('bookings', 0);
    });

    test('returns 422 when both car and driver are scheduled', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $car->schedules()->create([
            'start_time' => '2026-02-10 00:00:00',
            'end_time' => '2026-02-15 23:59:59',
        ]);
        $driver->schedules()->create([
            'start_time' => '2026-02-10 00:00:00',
            'end_time' => '2026-02-15 23:59:59',
        ]);

        $payload = bookingPayload([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-12',
            'end_date' => '2026-02-14',
        ]);

        $response = postJson("/api/v1/transactions/{$transaction->id}/book", $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected car is not available for the given dates.',
        ]);
        $this->assertDatabaseCount('bookings', 0);
    });

    test('returns 422 when required fields are missing', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        postJson("/api/v1/transactions/{$transaction->id}/book", [])->assertStatus(422);
    });

    test('returns 404 when transaction belongs to another user', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $payload = bookingPayload([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
        ]);

        postJson("/api/v1/transactions/{$transaction->id}/book", $payload)->assertStatus(404);
    });
});
