<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Transaction;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        'report_date' => '2026-06-25',
        'po_number' => 'PO-1001',
        'time_in' => '08:00',
        'time_out' => '17:00',
        'rate' => 1800,
        'odometer_in' => 1000,
        'odometer_out' => 1100,
        'fuel_liters' => 25.5,
        'fuel_amount' => 1800,
        'invoice_or_or_number' => 'INV-1001',
        'collection_amount' => 3500,
        'percentage' => 15.5,
        'destinations' => [
            ['from' => 'Quezon City', 'to' => 'Makati'],
            ['from' => 'Makati', 'to' => 'Pasig'],
        ],
    ], $overrides);
}

function tripReportBaseSetup(): array
{
    $owner = User::factory()->create();
    $customer = Customer::factory()->create(['name' => 'Acme Travel']);
    $transaction = Transaction::factory()->create([
        'user_id' => $owner->id,
        'customer_id' => $customer->id,
        'name' => 'Airport Transfer',
    ]);
    $car = Car::factory()->create([
        'make' => 'Toyota',
        'model' => 'Hiace',
        'plate_number' => 'ABC-1234',
    ]);
    $driverUser = User::factory()->create();
    $driver = Driver::factory()->forUser($driverUser)->create([
        'first_name' => 'John',
        'last_name' => 'Cruz',
    ]);
    $booking = Booking::factory()->create([
        'transaction_id' => $transaction->id,
        'car_id' => $car->id,
        'driver_id' => $driver->id,
    ]);

    return compact('owner', 'customer', 'transaction', 'car', 'driverUser', 'driver', 'booking');
}

describe('guest user', function () {
    test('gets 401 for trip report endpoints', function (string $method, ?string $suffix = null) {
        $setup = tripReportBaseSetup();
        $tripReport = TripReport::factory()->create(['booking_id' => $setup['booking']->id]);
        $url = "/api/v1/transactions/{$setup['transaction']->id}/bookings/{$setup['booking']->id}/trip-reports";

        if ($suffix !== null) {
            $url .= "/{$tripReport->id}";
        }

        $response = match ($method) {
            'get' => getJson($url),
            'post' => postJson($url, tripReportPayload()),
            'put' => putJson($url, tripReportPayload(['po_number' => 'UPDATED'])),
            'delete' => deleteJson($url),
        };

        $response->assertUnauthorized();
    })->with([
        ['get', null],
        ['post', null],
        ['get', 'show'],
        ['put', 'update'],
        ['delete', 'delete'],
    ]);
});

describe('admin user', function () {
    beforeEach(function () {
        $this->setup = tripReportBaseSetup();
        $this->admin = User::factory()->admin()->create();
        Sanctum::actingAs($this->admin);
        $this->baseUrl = "/api/v1/transactions/{$this->setup['transaction']->id}/bookings/{$this->setup['booking']->id}/trip-reports";
    });

    test('can create a trip report and stores snapshots', function () {
        $response = postJson($this->baseUrl, tripReportPayload())->assertCreated();

        $tripReportId = $response->json('data.id');

        assertDatabaseHas('trip_reports', [
            'id' => $tripReportId,
            'booking_id' => $this->setup['booking']->id,
            'driver_id_snapshot' => $this->setup['driver']->id,
            'driver_name_snapshot' => 'John Cruz',
            'car_id_snapshot' => $this->setup['car']->id,
            'car_make_snapshot' => 'Toyota',
            'car_model_snapshot' => 'Hiace',
            'car_plate_number_snapshot' => 'ABC-1234',
            'customer_id_snapshot' => $this->setup['customer']->id,
            'customer_name_snapshot' => 'Acme Travel',
            'transaction_name_snapshot' => 'Airport Transfer',
        ]);

        $response
            ->assertJsonPath('data.type', 'trip-report')
            ->assertJsonPath('data.attributes.driverNameSnapshot', 'John Cruz')
            ->assertJsonPath('data.attributes.carPlateNumberSnapshot', 'ABC-1234')
            ->assertJsonPath('data.attributes.destinations.0.from', 'Quezon City');
    });

    test('can list, show, update, and delete a trip report', function () {
        $tripReport = TripReport::factory()->create([
            'booking_id' => $this->setup['booking']->id,
            'driver_name_snapshot' => 'John Cruz',
            'car_plate_number_snapshot' => 'ABC-1234',
        ]);

        getJson($this->baseUrl)
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');

        getJson("{$this->baseUrl}/{$tripReport->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $tripReport->id);

        putJson("{$this->baseUrl}/{$tripReport->id}", [
            'po_number' => 'PO-2002',
            'time_out' => '18:00',
        ])
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.poNumber', 'PO-2002')
            ->assertJsonPath('data.attributes.timeOut', '18:00');

        deleteJson("{$this->baseUrl}/{$tripReport->id}")
            ->assertNoContent();

        assertDatabaseMissing('trip_reports', ['id' => $tripReport->id]);
    });
});

describe('assigned driver user', function () {
    beforeEach(function () {
        $this->setup = tripReportBaseSetup();
        Sanctum::actingAs($this->setup['driverUser']);
        $this->baseUrl = "/api/v1/transactions/{$this->setup['transaction']->id}/bookings/{$this->setup['booking']->id}/trip-reports";
    });

    test('can create, list, show, update, and delete trip reports', function () {
        $createResponse = postJson($this->baseUrl, tripReportPayload())->assertCreated();
        $tripReportId = $createResponse->json('data.id');

        getJson($this->baseUrl)
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');

        getJson("{$this->baseUrl}/{$tripReportId}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $tripReportId);

        putJson("{$this->baseUrl}/{$tripReportId}", [
            'collection_amount' => 4500,
        ])
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.collectionAmount', 4500);

        deleteJson("{$this->baseUrl}/{$tripReportId}")
            ->assertNoContent();
    });
});

describe('forbidden access', function () {
    test('forbids a non-assigned driver for all trip report actions', function (string $method, ?string $suffix = null) {
        $setup = tripReportBaseSetup();
        $otherDriverUser = User::factory()->create();
        Driver::factory()->forUser($otherDriverUser)->create();
        Sanctum::actingAs($otherDriverUser);

        $tripReport = TripReport::factory()->create(['booking_id' => $setup['booking']->id]);
        $url = "/api/v1/transactions/{$setup['transaction']->id}/bookings/{$setup['booking']->id}/trip-reports";

        if ($suffix !== null) {
            $url .= "/{$tripReport->id}";
        }

        $response = match ($method) {
            'get' => getJson($url),
            'post' => postJson($url, tripReportPayload()),
            'put' => putJson($url, ['po_number' => 'NOPE']),
            'delete' => deleteJson($url),
        };

        $response->assertForbidden();
    })->with([
        ['get', null],
        ['post', null],
        ['get', 'show'],
        ['put', 'update'],
        ['delete', 'delete'],
    ]);

    test('forbids a regular non-driver user', function () {
        $setup = tripReportBaseSetup();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        postJson(
            "/api/v1/transactions/{$setup['transaction']->id}/bookings/{$setup['booking']->id}/trip-reports",
            tripReportPayload()
        )->assertForbidden();
    });
});

describe('route integrity', function () {
    test('returns 404 when booking does not belong to the transaction', function () {
        $setup = tripReportBaseSetup();
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $otherTransaction = Transaction::factory()->create();

        postJson(
            "/api/v1/transactions/{$otherTransaction->id}/bookings/{$setup['booking']->id}/trip-reports",
            tripReportPayload()
        )->assertNotFound();
    });

    test('returns 404 when trip report does not belong to the booking', function () {
        $setup = tripReportBaseSetup();
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $otherBooking = Booking::factory()->create(['transaction_id' => $setup['transaction']->id]);
        $tripReport = TripReport::factory()->create(['booking_id' => $otherBooking->id]);

        getJson("/api/v1/transactions/{$setup['transaction']->id}/bookings/{$setup['booking']->id}/trip-reports/{$tripReport->id}")
            ->assertNotFound();
    });
});

describe('snapshot behavior', function () {
    test('keeps saved snapshots after related records change', function () {
        $setup = tripReportBaseSetup();
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = postJson(
            "/api/v1/transactions/{$setup['transaction']->id}/bookings/{$setup['booking']->id}/trip-reports",
            tripReportPayload()
        )->assertCreated();

        $tripReport = TripReport::findOrFail($response->json('data.id'));

        $newDriver = Driver::factory()->create([
            'first_name' => 'Mark',
            'last_name' => 'Reyes',
        ]);

        $setup['booking']->update([
            'driver_id' => $newDriver->id,
        ]);
        $setup['car']->update([
            'make' => 'Nissan',
            'model' => 'Urvan',
            'plate_number' => 'XYZ-9876',
        ]);
        $setup['customer']->update([
            'name' => 'Updated Customer',
        ]);
        $setup['transaction']->update([
            'name' => 'Updated Transaction',
        ]);

        expect($tripReport->fresh()->driver_name_snapshot)->toBe('John Cruz')
            ->and($tripReport->fresh()->car_make_snapshot)->toBe('Toyota')
            ->and($tripReport->fresh()->car_model_snapshot)->toBe('Hiace')
            ->and($tripReport->fresh()->car_plate_number_snapshot)->toBe('ABC-1234')
            ->and($tripReport->fresh()->customer_name_snapshot)->toBe('Acme Travel')
            ->and($tripReport->fresh()->transaction_name_snapshot)->toBe('Airport Transfer');
    });
});

describe('validation', function () {
    test('validates malformed destinations, invalid time ordering, and invalid odometer ordering', function () {
        $setup = tripReportBaseSetup();
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        postJson(
            "/api/v1/transactions/{$setup['transaction']->id}/bookings/{$setup['booking']->id}/trip-reports",
            tripReportPayload([
                'time_in' => '18:00',
                'time_out' => '08:00',
                'odometer_in' => 2000,
                'odometer_out' => 1000,
                'destinations' => [
                    ['from' => ['not-a-string'], 'to' => 'Makati'],
                ],
            ])
        )
            ->assertUnprocessable()
            ->assertJsonFragment([
                'pointer' => '/data/attributes/time_out',
            ])
            ->assertJsonFragment([
                'pointer' => '/data/attributes/odometer_out',
            ])
            ->assertJsonFragment([
                'pointer' => '/data/attributes/destinations/0/from',
            ]);
    });
});

describe('booking deletion cascade', function () {
    test('deleting a booking removes related trip reports', function () {
        $setup = tripReportBaseSetup();
        $tripReport = TripReport::factory()->create(['booking_id' => $setup['booking']->id]);

        Sanctum::actingAs($setup['owner']);

        deleteJson("/api/v1/transactions/{$setup['transaction']->id}/bookings/{$setup['booking']->id}")
            ->assertNoContent();

        assertDatabaseMissing('trip_reports', ['id' => $tripReport->id]);
    });
});
