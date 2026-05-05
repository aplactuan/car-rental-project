<?php

use App\Models\Car;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('only seeds required data in production environment', function () {
    config(['app.env' => 'production']);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and(Car::query()->count())->toBe(0)
        ->and(Driver::query()->count())->toBe(0)
        ->and(Customer::query()->count())->toBe(0);
});

it('seeds sample data outside production environment', function () {
    config(['app.env' => 'testing']);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and(Car::query()->count())->toBe(10)
        ->and(Driver::query()->count())->toBe(10)
        ->and(Customer::query()->count())->toBe(10);
});
