<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Driver;
use App\Models\Transaction;
use App\Repositories\Eloquent\BookingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new BookingRepository(new Booking);
});

test('getCarIdsBookedInPeriod returns car ids with overlapping bookings', function () {
    $user = \App\Models\User::factory()->create();
    $car1 = Car::factory()->create();
    $car2 = Car::factory()->create();
    $driver = Driver::factory()->create();

    $transaction = Transaction::factory()->create(['user_id' => $user->id]);
    $transaction->bookings()->create([
        'car_id' => $car1->id,
        'driver_id' => $driver->id,
        'start_date' => '2026-02-10',
        'end_date' => '2026-02-15',
        'note' => null,
    ]);

    $ids = $this->repository->getCarIdsBookedInPeriod('2026-02-12', '2026-02-14');

    expect($ids)->toContain($car1->id);
    expect($ids)->not->toContain($car2->id);
});

test('getCarIdsBookedInPeriod excludes non-overlapping period', function () {
    $user = \App\Models\User::factory()->create();
    $car = Car::factory()->create();
    $driver = Driver::factory()->create();
    $transaction = Transaction::factory()->create(['user_id' => $user->id]);
    $transaction->bookings()->create([
        'car_id' => $car->id,
        'driver_id' => $driver->id,
        'start_date' => '2026-02-10',
        'end_date' => '2026-02-15',
        'note' => null,
    ]);

    $ids = $this->repository->getCarIdsBookedInPeriod('2026-02-20', '2026-02-25');

    expect($ids)->not->toContain($car->id);
});

test('getDriverIdsBookedInPeriod returns driver ids with overlapping bookings', function () {
    $user = \App\Models\User::factory()->create();
    $car = Car::factory()->create();
    $driver1 = Driver::factory()->create();
    $driver2 = Driver::factory()->create();

    $transaction = Transaction::factory()->create(['user_id' => $user->id]);
    $transaction->bookings()->create([
        'car_id' => $car->id,
        'driver_id' => $driver1->id,
        'start_date' => '2026-02-10',
        'end_date' => '2026-02-15',
        'note' => null,
    ]);

    $ids = $this->repository->getDriverIdsBookedInPeriod('2026-02-12', '2026-02-14');

    expect($ids)->toContain($driver1->id);
    expect($ids)->not->toContain($driver2->id);
});
