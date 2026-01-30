<?php

use App\Models\Car;
use App\Models\Driver;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Eloquent\TransactionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new TransactionRepository(new Transaction);
});

test('create persists transaction and bookings in one go', function () {
    $user = User::factory()->create();
    $car = Car::factory()->create();
    $driver = Driver::factory()->create();

    $data = [
        'user_id' => $user->id,
        'bookings' => [
            [
                'car_id' => $car->id,
                'driver_id' => $driver->id,
                'note' => 'Test note',
                'start_date' => now()->addDays(1)->format('Y-m-d'),
                'end_date' => now()->addDays(3)->format('Y-m-d'),
            ],
        ],
    ];

    $transaction = $this->repository->create($data);

    expect($transaction)->toBeInstanceOf(Transaction::class);
    expect($transaction->user_id)->toBe($user->id);
    expect($transaction->bookings)->toHaveCount(1);
    expect($transaction->bookings->first()->car_id)->toBe($car->id);
    expect($transaction->bookings->first()->driver_id)->toBe($driver->id);
});

test('create with multiple bookings persists all', function () {
    $user = User::factory()->create();
    $car1 = Car::factory()->create();
    $car2 = Car::factory()->create();
    $driver1 = Driver::factory()->create();
    $driver2 = Driver::factory()->create();

    $data = [
        'user_id' => $user->id,
        'bookings' => [
            [
                'car_id' => $car1->id,
                'driver_id' => $driver1->id,
                'note' => null,
                'start_date' => '2026-02-01',
                'end_date' => '2026-02-05',
            ],
            [
                'car_id' => $car2->id,
                'driver_id' => $driver2->id,
                'note' => null,
                'start_date' => '2026-02-10',
                'end_date' => '2026-02-15',
            ],
        ],
    ];

    $transaction = $this->repository->create($data);

    expect($transaction->bookings)->toHaveCount(2);
});
