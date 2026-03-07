<?php

use App\Models\Car;
use App\Models\Driver;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot list bookings when not authenticated', function () {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bookings")->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can list bookings for own transaction', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-15',
            'note' => 'First booking',
        ]);
        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-20',
            'end_date' => '2026-02-25',
            'note' => 'Second booking',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'booking')
            ->assertJsonPath('data.0.attributes.note', 'First booking')
            ->assertJsonPath('data.0.attributes.startDate', '2026-02-10')
            ->assertJsonPath('data.0.attributes.endDate', '2026-02-15')
            ->assertJsonPath('data.0.relationships.car.data.id', $car->id)
            ->assertJsonPath('data.0.relationships.driver.data.id', $driver->id);
    });

    test('returns empty array when transaction has no bookings', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    test('respects per_page pagination parameter', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        foreach (range(1, 5) as $i) {
            $transaction->bookings()->create([
                'car_id' => $car->id,
                'driver_id' => $driver->id,
                'start_date' => "2026-02-0{$i}",
                'end_date' => "2026-02-1{$i}",
                'note' => "Booking {$i}",
            ]);
        }

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings?per_page=2");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    });

    test('returns 404 when transaction belongs to another user', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bookings")->assertStatus(404);
    });

    test('validates per_page parameter', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bookings?per_page=0")->assertStatus(422);
        getJson("/api/v1/transactions/{$transaction->id}/bookings?per_page=101")->assertStatus(422);
    });
});
