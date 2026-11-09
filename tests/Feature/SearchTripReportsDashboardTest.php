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
    test('cannot search trip reports', function () {
        getJson('/api/v1/dashboard/trip-reports/search?q=TR-1')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create());
    });

    test('requires a search query', function () {
        getJson('/api/v1/dashboard/trip-reports/search')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/q');
    });

    test('returns an empty collection when nothing matches', function () {
        TripReport::factory()->create([
            'trip_report_no' => 'TR-AAAA',
            'driver' => 'Alpha Driver',
            'destinations' => 'Manila',
        ]);

        getJson('/api/v1/dashboard/trip-reports/search?q=ZZZ-NO-MATCH')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    test('searches by trip report number, driver, and destinations', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $byNumber = TripReport::factory()->for($purchaseOrder)->create([
            'trip_report_no' => 'TR-SEARCH-001',
            'driver' => 'Unrelated Driver',
            'destinations' => 'Cebu',
            'report_date' => '2026-08-12',
        ]);
        $byDriver = TripReport::factory()->for($purchaseOrder)->create([
            'trip_report_no' => 'TR-OTHER-002',
            'driver' => 'Jordan Searchable',
            'destinations' => 'Davao',
            'report_date' => '2026-08-11',
        ]);
        $byDestination = TripReport::factory()->for($purchaseOrder)->create([
            'trip_report_no' => 'TR-OTHER-003',
            'driver' => 'Other Driver',
            'destinations' => 'Searchable City',
            'report_date' => '2026-08-10',
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'trip_report_no' => 'TR-MISS-004',
            'driver' => 'No Match',
            'destinations' => 'Nowhere',
            'report_date' => '2026-08-09',
        ]);

        getJson('/api/v1/dashboard/trip-reports/search?q=search')
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $byNumber->id)
            ->assertJsonPath('data.1.id', $byDriver->id)
            ->assertJsonPath('data.2.id', $byDestination->id)
            ->assertJsonPath('data.0.attributes.customerId', $purchaseOrder->customer_id)
            ->assertJsonPath('data.0.relationships.purchaseOrder.data.id', $purchaseOrder->id)
            ->assertJsonMissingPath('data.0.attributes.amount');
    });

    test('limits search results to twenty reports', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        collect(range(1, 21))->each(
            fn (int $day): TripReport => TripReport::factory()
                ->for($purchaseOrder)
                ->create([
                    'trip_report_no' => sprintf('TR-LIMIT-%02d', $day),
                    'driver' => "Limit Driver {$day}",
                    'report_date' => sprintf('2026-08-%02d', $day),
                ])
        );

        getJson('/api/v1/dashboard/trip-reports/search?q=LIMIT')
            ->assertSuccessful()
            ->assertJsonCount(20, 'data');
    });

    test('loads search results with a bounded query set', function () {
        TripReport::factory()->create([
            'trip_report_no' => 'TR-BOUNDED',
            'driver' => 'Bounded Driver',
        ]);

        DB::enableQueryLog();

        getJson('/api/v1/dashboard/trip-reports/search?q=BOUNDED')->assertSuccessful();

        // trip reports + purchase orders (for customerId)
        expect(DB::getQueryLog())->toHaveCount(2);
    });
});
