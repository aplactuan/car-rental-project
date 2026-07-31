<?php

use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

function tripReportPayload(array $overrides = []): array
{
    return array_merge([
        'report_date' => '2026-07-28',
        'trip_start' => '2026-07-27',
        'trip_end' => '2026-07-28',
        'driver' => 'Juan Dela Cruz',
        'destinations' => 'Manila to Quezon City',
        'amount' => 1_500,
    ], $overrides);
}

describe('guest user', function () {
    test('cannot manage trip reports when not authenticated', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $tripReport = TripReport::factory()->for($purchaseOrder)->create();

        postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports", tripReportPayload())->assertUnauthorized();
        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports")->assertUnauthorized();
        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports/{$tripReport->id}")->assertUnauthorized();
        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports/{$tripReport->id}", ['amount' => 2_000])->assertUnauthorized();
        deleteJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports/{$tripReport->id}")->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
    });

    test('can create a trip report with its optional image', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $payload = tripReportPayload([
            'driver' => 'Maria Santos',
            'trip_report_image' => UploadedFile::fake()->image('trip-report.jpg'),
        ]);

        $response = postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports", $payload);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'trip-report')
            ->assertJsonPath('data.attributes.reportDate', $payload['report_date'])
            ->assertJsonPath('data.attributes.tripStart', $payload['trip_start'])
            ->assertJsonPath('data.attributes.tripEnd', $payload['trip_end'])
            ->assertJsonPath('data.attributes.driver', $payload['driver'])
            ->assertJsonPath('data.attributes.destinations', $payload['destinations'])
            ->assertJsonPath('data.attributes.amount', $payload['amount'])
            ->assertJsonPath('data.relationships.purchaseOrder.data.id', $purchaseOrder->id);

        expect($response->json('data.attributes.tripReportImageUrl'))->not->toBeNull();

        assertDatabaseHas('trip_reports', [
            'purchase_order_id' => $purchaseOrder->id,
            'driver' => $payload['driver'],
            'destinations' => $payload['destinations'],
            'amount' => $payload['amount'],
        ]);

        $tripReport = TripReport::query()->latest('created_at')->firstOrFail();
        expect($tripReport->trip_start->toDateString())->toBe($payload['trip_start'])
            ->and($tripReport->trip_end->toDateString())->toBe($payload['trip_end'])
            ->and($tripReport->getFirstMedia('trip_report_image'))->not->toBeNull();
    });

    test('can create a trip report without an image', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports", tripReportPayload())
            ->assertCreated()
            ->assertJsonPath('data.attributes.tripReportImageUrl', null);
    });

    test('lists and shows only the purchase order trip reports', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $tripReport = TripReport::factory()->for($purchaseOrder)->create();
        TripReport::factory()->create();

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $tripReport->id);

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports/{$tripReport->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $tripReport->id);
    });

    test('cannot show a trip report from another purchase order', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $tripReport = TripReport::factory()->create();

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports/{$tripReport->id}")
            ->assertNotFound();
    });

    test('can update a trip report and replace its image', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $tripReport = TripReport::factory()->for($purchaseOrder)->create();

        $payload = [
            'report_date' => '2026-08-01',
            'trip_start' => '2026-07-30',
            'trip_end' => '2026-08-01',
            'driver' => 'Pedro Reyes',
            'destinations' => 'Makati to Pasig',
            'amount' => 2_500,
            'trip_report_image' => UploadedFile::fake()->image('updated-trip-report.png'),
        ];

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports/{$tripReport->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.attributes.reportDate', $payload['report_date'])
            ->assertJsonPath('data.attributes.tripStart', $payload['trip_start'])
            ->assertJsonPath('data.attributes.tripEnd', $payload['trip_end'])
            ->assertJsonPath('data.attributes.driver', $payload['driver'])
            ->assertJsonPath('data.attributes.destinations', $payload['destinations'])
            ->assertJsonPath('data.attributes.amount', $payload['amount']);

        assertDatabaseHas('trip_reports', [
            'id' => $tripReport->id,
            'driver' => $payload['driver'],
            'destinations' => $payload['destinations'],
            'amount' => $payload['amount'],
        ]);

        $tripReport = $tripReport->fresh();

        expect($tripReport->trip_start->toDateString())->toBe($payload['trip_start'])
            ->and($tripReport->trip_end->toDateString())->toBe($payload['trip_end'])
            ->and($tripReport->getMedia('trip_report_image'))->toHaveCount(1);
    });

    test('validates the trip report attributes', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports", [
            'report_date' => 'not-a-date',
            'trip_start' => 'not-a-date',
            'trip_end' => 'not-a-date',
            'driver' => '',
            'destinations' => '',
            'amount' => -1,
            'trip_report_image' => UploadedFile::fake()->create('trip-report.pdf', 100, 'application/pdf'),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/report_date');
    });

    test('requires trip start and trip end dates', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports", [
            'report_date' => '2026-07-28',
            'driver' => 'Juan Dela Cruz',
            'destinations' => 'Manila to Quezon City',
            'amount' => 1_500,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/trip_start')
            ->assertJsonPath('errors.1.source.pointer', '/data/attributes/trip_end');
    });

    test('can delete a trip report', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $tripReport = TripReport::factory()->for($purchaseOrder)->create();

        deleteJson("/api/v1/purchase-orders/{$purchaseOrder->id}/trip-reports/{$tripReport->id}")
            ->assertNoContent();

        assertDatabaseMissing('trip_reports', ['id' => $tripReport->id]);
    });
});
