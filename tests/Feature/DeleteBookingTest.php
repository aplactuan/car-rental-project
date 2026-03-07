<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Driver;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot delete a booking when not authenticated', function () {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
        ]);

        deleteJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}")
            ->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can delete a booking from own transaction', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
        ]);

        deleteJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('bookings', [
            'id' => $booking->id,
        ]);
    });

    test('returns 404 when transaction belongs to another user', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
        ]);

        deleteJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}")
            ->assertNotFound();
    });

    test('returns 404 when booking does not belong to transaction', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $otherTransaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $otherTransaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
        ]);

        deleteJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}")
            ->assertNotFound();
    });
});
