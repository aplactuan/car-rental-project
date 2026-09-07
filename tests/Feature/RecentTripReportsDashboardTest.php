<?php

use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot view recent trip reports', function () {
        getJson('/api/v1/dashboard/trip-reports/recent')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create());
    });

    test('returns an empty collection when there are no trip reports', function () {
        getJson('/api/v1/dashboard/trip-reports/recent')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    test('returns only the ten most recent trip reports', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $reports = collect(range(1, 11))->map(
            fn (int $day): TripReport => TripReport::factory()
                ->for($purchaseOrder)
                ->create([
                    'driver' => "Driver {$day}",
                    'report_date' => "2026-08-{$day}",
                ])
        );

        getJson('/api/v1/dashboard/trip-reports/recent')
            ->assertSuccessful()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.id', $reports[10]->id)
            ->assertJsonPath('data.0.attributes.driver', 'Driver 11')
            ->assertJsonPath('data.0.attributes.reportDate', '2026-08-11')
            ->assertJsonPath('data.0.relationships.purchaseOrder.data.id', $purchaseOrder->id)
            ->assertJsonMissing(['id' => $reports[0]->id])
            ->assertJsonMissingPath('data.0.attributes.amount')
            ->assertJsonMissingPath('data.0.attributes.tripReportImageUrl');
    });

    test('orders reports on the same date by driver name', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $bravo = TripReport::factory()->for($purchaseOrder)->create([
            'driver' => 'Bravo Driver',
            'report_date' => '2026-08-10',
        ]);
        $alpha = TripReport::factory()->for($purchaseOrder)->create([
            'driver' => 'Alpha Driver',
            'report_date' => '2026-08-10',
        ]);

        getJson('/api/v1/dashboard/trip-reports/recent')
            ->assertSuccessful()
            ->assertJsonPath('data.0.id', $alpha->id)
            ->assertJsonPath('data.1.id', $bravo->id);
    });

    test('loads recent reports with one bounded query', function () {
        TripReport::factory()->count(3)->create();

        DB::enableQueryLog();

        getJson('/api/v1/dashboard/trip-reports/recent')->assertSuccessful();

        expect(DB::getQueryLog())->toHaveCount(1);
    });
});
