<?php

use App\Models\Car;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

test('guest cannot list availability', function () {
    getJson('/api/v1/availability?type=car&start=2026-02-10 09:00:00&end=2026-02-10 12:00:00')
        ->assertUnauthorized();
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it validates required availability parameters', function () {
        getJson('/api/v1/availability')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/type')
            ->assertJsonPath('errors.1.source.pointer', '/data/attributes/start')
            ->assertJsonPath('errors.2.source.pointer', '/data/attributes/end');
    });

    test('it returns only available cars for the requested interval', function () {
        $availableCar = Car::factory()->create(['plate_number' => 'AVAIL-AV-001']);
        $scheduledCar = Car::factory()->create(['plate_number' => 'BOOK-AV-001']);

        $scheduledCar->schedules()->create([
            'start_time' => '2026-02-10 09:00:00',
            'end_time' => '2026-02-10 11:00:00',
        ]);

        $response = getJson('/api/v1/availability?type=car&start=2026-02-10 10:00:00&end=2026-02-10 12:00:00');

        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->all();

        expect($ids)->toContain($availableCar->id);
        expect($ids)->not->toContain($scheduledCar->id);
    });

    test('it returns only available drivers for the requested interval', function () {
        $availableDriver = Driver::factory()->create(['license_number' => 'DRV-AV-001']);
        $scheduledDriver = Driver::factory()->create(['license_number' => 'DRV-BK-001']);

        $scheduledDriver->schedules()->create([
            'start_time' => '2026-02-10 09:00:00',
            'end_time' => '2026-02-10 11:00:00',
        ]);

        $response = getJson('/api/v1/availability?type=driver&start=2026-02-10 10:00:00&end=2026-02-10 12:00:00');

        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->all();

        expect($ids)->toContain($availableDriver->id);
        expect($ids)->not->toContain($scheduledDriver->id);
    });
});
