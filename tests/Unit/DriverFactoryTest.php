<?php

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('withUser creates a linked user with matching name', function () {
    $driver = Driver::factory()->withUser()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);

    $driver->refresh();

    expect($driver->user_id)->not->toBeNull();
    expect($driver->user)->not->toBeNull();
    expect($driver->user->name)->toBe('Jane Doe');
});

test('withUser does not create a user when driver already has one', function () {
    $existingUser = User::factory()->create();

    $driver = Driver::factory()
        ->forUser($existingUser)
        ->withUser()
        ->create();

    expect(User::query()->count())->toBe(1);
    expect($driver->user_id)->toBe($existingUser->id);
});

test('default driver factory does not create a user', function () {
    $driver = Driver::factory()->create();

    expect($driver->user_id)->toBeNull();
    expect(User::query()->count())->toBe(0);
});
