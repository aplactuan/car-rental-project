<?php

use App\Models\Car;
use App\Models\Driver;
use App\Models\Schedule;
use App\Repositories\Eloquent\ScheduleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new ScheduleRepository(new Schedule);
});

test('getDriverIdsScheduledInPeriod returns driver ids with overlapping schedules', function () {
    $car = Car::factory()->create();
    $driverScheduled = Driver::factory()->create();
    $driverAvailable = Driver::factory()->create();

    $car->schedules()->create([
        'start_time' => '2026-02-10 00:00:00',
        'end_time' => '2026-02-15 23:59:59',
    ]);

    $driverScheduled->schedules()->create([
        'start_time' => '2026-02-10 00:00:00',
        'end_time' => '2026-02-15 23:59:59',
    ]);

    $ids = $this->repository->getDriverIdsScheduledInPeriod('2026-02-12', '2026-02-14');

    expect($ids)->toContain($driverScheduled->id);
    expect($ids)->not->toContain($driverAvailable->id);
    expect($ids)->not->toContain($car->id);
});

test('getDriverIdsScheduledInPeriod excludes non-overlapping schedules', function () {
    $driver = Driver::factory()->create();

    $driver->schedules()->create([
        'start_time' => '2026-02-10 00:00:00',
        'end_time' => '2026-02-15 23:59:59',
    ]);

    $ids = $this->repository->getDriverIdsScheduledInPeriod('2026-02-20', '2026-02-25');

    expect($ids)->not->toContain($driver->id);
});
