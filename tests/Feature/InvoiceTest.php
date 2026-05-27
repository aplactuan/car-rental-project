<?php

use App\Models\Bill;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot get an invoice when not authenticated', function () {
        $transaction = Transaction::factory()->create();

        getJson("/api/v1/transactions/{$transaction->id}/bill/invoice")->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can get invoice with full details', function () {
        $customer = Customer::factory()->create(['name' => 'Jane Smith', 'type' => 'personal']);
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'name' => 'Summer rental',
        ]);

        $car = Car::factory()->create(['make' => 'Toyota', 'model' => 'Camry', 'plate_number' => 'ABC-1234']);
        $driver = Driver::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'price' => 500000,
            'note' => 'Airport pickup',
        ]);

        $bill = Bill::factory()->create([
            'transaction_id' => $transaction->id,
            'amount' => 500000,
            'status' => 'issued',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bill/invoice");

        $response->assertOk()
            ->assertJsonPath('invoiceNumber', $bill->invoice_number)
            ->assertJsonPath('billNumber', $bill->bill_number)
            ->assertJsonPath('status', 'issued')
            ->assertJsonPath('amount', 500000)
            ->assertJsonPath('transaction.name', 'Summer rental')
            ->assertJsonPath('customer.name', 'Jane Smith')
            ->assertJsonPath('customer.type', 'personal')
            ->assertJsonPath('bookings.0.price', 500000)
            ->assertJsonPath('bookings.0.note', 'Airport pickup')
            ->assertJsonPath('bookings.0.car.make', 'Toyota')
            ->assertJsonPath('bookings.0.car.model', 'Camry')
            ->assertJsonPath('bookings.0.car.plateNumber', 'ABC-1234')
            ->assertJsonPath('bookings.0.driver.firstName', 'John')
            ->assertJsonPath('bookings.0.driver.lastName', 'Doe');
    });

    test('invoice contains all required top-level fields', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        Bill::factory()->create(['transaction_id' => $transaction->id]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bill/invoice");

        $response->assertOk()
            ->assertJsonStructure([
                'invoiceNumber',
                'billNumber',
                'status',
                'issuedAt',
                'dueAt',
                'paidAt',
                'amount',
                'notes',
                'transaction' => ['name'],
                'customer',
                'bookings',
            ]);
    });

    test('invoice includes multiple bookings', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        Booking::factory()->count(3)->create(['transaction_id' => $transaction->id]);

        Bill::factory()->create(['transaction_id' => $transaction->id]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bill/invoice");

        $response->assertOk();
        expect($response->json('bookings'))->toHaveCount(3);
    });

    test('booking in invoice has car and driver as null when not assigned', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        Booking::factory()->create([
            'transaction_id' => $transaction->id,
            'car_id' => null,
            'driver_id' => null,
        ]);

        Bill::factory()->create(['transaction_id' => $transaction->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bill/invoice")
            ->assertOk()
            ->assertJsonPath('bookings.0.car', null)
            ->assertJsonPath('bookings.0.driver', null);
    });

    test('returns 404 when transaction has no bill', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bill/invoice")->assertNotFound();
    });

    test('returns 404 when accessing another users transaction bill', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);
        Bill::factory()->create(['transaction_id' => $transaction->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bill/invoice")->assertNotFound();
    });

    test('invoice response does not include JSON:API wrapper', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        Bill::factory()->create(['transaction_id' => $transaction->id]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bill/invoice");

        $response->assertOk();
        expect($response->json('data'))->toBeNull();
    });
});
