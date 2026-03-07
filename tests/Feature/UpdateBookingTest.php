<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Driver;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot update a booking when not authenticated', function () {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-20',
            'end_date' => '2026-02-25',
        ]);

        putJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}", [
            'note' => 'Updated note',
        ])->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can update a booking when car and driver are available', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-20',
            'end_date' => '2026-02-25',
            'note' => 'Original note',
        ]);

        $response = putJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}", [
            'note' => 'Updated note',
            'start_date' => '2026-02-26',
            'end_date' => '2026-02-28',
        ]);

        $response->assertStatus(200);
        $json = $response->json();
        expect($json['data']['attributes']['note'])->toBe('Updated note');
        expect($json['data']['attributes']['startDate'])->toBe('2026-02-26');
        expect($json['data']['attributes']['endDate'])->toBe('2026-02-28');

        $booking->refresh();
        expect($booking->note)->toBe('Updated note');
        expect($booking->start_date->format('Y-m-d'))->toBe('2026-02-26');
        expect($booking->end_date->format('Y-m-d'))->toBe('2026-02-28');
    });

    test('can update only the note without changing dates or car or driver', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-20',
            'end_date' => '2026-02-25',
            'note' => 'Original note',
        ]);

        $response = putJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}", [
            'note' => 'New note only',
        ]);

        $response->assertStatus(200);
        expect($response->json('data.attributes.note'))->toBe('New note only');
        expect($response->json('data.attributes.startDate'))->toBe('2026-02-20');
        expect($response->json('data.attributes.endDate'))->toBe('2026-02-25');
    });

    test('returns 422 when car is scheduled for the new dates', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-20',
            'end_date' => '2026-02-25',
        ]);

        $car->schedules()->create([
            'start_time' => '2026-02-26 00:00:00',
            'end_time' => '2026-02-28 23:59:59',
        ]);

        $response = putJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}", [
            'start_date' => '2026-02-26',
            'end_date' => '2026-02-28',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected car is not available for the given dates.',
        ]);
    });

    test('returns 422 when driver is scheduled for the new dates', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-20',
            'end_date' => '2026-02-25',
        ]);

        $driver->schedules()->create([
            'start_time' => '2026-02-26 00:00:00',
            'end_time' => '2026-02-28 23:59:59',
        ]);

        $response = putJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}", [
            'start_date' => '2026-02-26',
            'end_date' => '2026-02-28',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected driver is not available for the given dates.',
        ]);
    });

    test('returns 422 when updating to a car that is scheduled for the period', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $otherCar = Car::factory()->create();
        $driver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-20',
            'end_date' => '2026-02-25',
        ]);

        $otherCar->schedules()->create([
            'start_time' => '2026-02-20 00:00:00',
            'end_time' => '2026-02-25 23:59:59',
        ]);

        $response = putJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}", [
            'car_id' => $otherCar->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected car is not available for the given dates.',
        ]);
    });

    test('returns 422 when updating to a driver that is scheduled for the period', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $otherDriver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-20',
            'end_date' => '2026-02-25',
        ]);

        $otherDriver->schedules()->create([
            'start_time' => '2026-02-20 00:00:00',
            'end_time' => '2026-02-25 23:59:59',
        ]);

        $response = putJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}", [
            'driver_id' => $otherDriver->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected driver is not available for the given dates.',
        ]);
    });

    test('can update to different car and driver when both are available', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $otherCar = Car::factory()->create();
        $driver = Driver::factory()->create();
        $otherDriver = Driver::factory()->create();
        $booking = Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-20',
            'end_date' => '2026-02-25',
        ]);

        $response = putJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}", [
            'car_id' => $otherCar->id,
            'driver_id' => $otherDriver->id,
        ]);

        $response->assertStatus(200);
        expect($response->json('data.relationships.car.data.id'))->toBe($otherCar->id);
        expect($response->json('data.relationships.driver.data.id'))->toBe($otherDriver->id);

        $booking->refresh();
        expect($booking->car_id)->toBe($otherCar->id);
        expect($booking->driver_id)->toBe($otherDriver->id);
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

        putJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}", [
            'note' => 'Updated',
        ])->assertStatus(404);
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

        putJson("/api/v1/transactions/{$transaction->id}/bookings/{$booking->id}", [
            'note' => 'Updated',
        ])->assertStatus(404);
    });
});
