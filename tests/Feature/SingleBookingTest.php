<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Driver;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot view a booking when not authenticated', function () {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);
        $booking = Booking::factory()->create(['transaction_id' => $transaction->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}")
            ->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can view a single booking with transaction attributes', function () {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Corporate fleet deal',
        ]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'note' => 'Airport pickup',
        ]);

        getJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'booking')
            ->assertJsonPath('data.attributes.note', 'Airport pickup')
            ->assertJsonPath('data.relationships.transaction.data.id', $transaction->id)
            ->assertJsonPath('data.relationships.transaction.data.attributes.name', 'Corporate fleet deal')
            ->assertJsonPath('data.relationships.transaction.data.attributes.customerId', $transaction->customer_id);
    });

    test('returns 404 when transaction belongs to another user', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);
        $booking = Booking::factory()->create(['transaction_id' => $transaction->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}")
            ->assertNotFound();
    });

    test('returns 404 when booking does not belong to transaction', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $otherTransaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $booking = Booking::factory()->create(['transaction_id' => $otherTransaction->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}")
            ->assertNotFound();
    });
});
