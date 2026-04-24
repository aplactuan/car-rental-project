<?php

use App\Models\Car;
use App\Models\Driver;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    afterEach(function () {
        Carbon::setTestNow();
    });

    test('can list bookings for own transaction', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-10 10:00:00',
            'end_date' => '2026-02-15 10:00:00',
            'note' => 'First booking',
        ]);
        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-02-20 10:00:00',
            'end_date' => '2026-02-25 10:00:00',
            'note' => 'Second booking',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'booking')
            ->assertJsonPath('data.0.attributes.note', 'Second booking')
            ->assertJsonPath('data.0.attributes.startDate', '2026-02-20 10:00:00')
            ->assertJsonPath('data.0.attributes.endDate', '2026-02-25 10:00:00')
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

    test('accepts booking filter parameters', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        getJson("/api/v1/transactions/{$transaction->id}/bookings?status=upcoming&period=month&car_id={$car->id}&driver_id={$driver->id}")
            ->assertSuccessful();
    });

    test('passes validated filters to the repository', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $repository = Mockery::mock(BookingRepositoryInterface::class);
        $repository->shouldReceive('getByTransaction')
            ->once()
            ->with(
                $transaction->id,
                [
                    'status' => 'upcoming',
                    'period' => 'month',
                    'car_id' => $car->id,
                    'driver_id' => $driver->id,
                ],
                null
            )
            ->andReturn(collect());

        app()->instance(BookingRepositoryInterface::class, $repository);

        getJson("/api/v1/transactions/{$transaction->id}/bookings?status=upcoming&period=month&car_id={$car->id}&driver_id={$driver->id}")
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    test('validates status filter parameter', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bookings?status=invalid")->assertStatus(422);
    });

    test('validates period filter parameter', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bookings?period=year")->assertStatus(422);
    });

    test('validates car_id filter parameter', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bookings?car_id=not-a-uuid")->assertStatus(422);
        getJson("/api/v1/transactions/{$transaction->id}/bookings?car_id=11111111-1111-1111-1111-111111111111")->assertStatus(422);
    });

    test('validates driver_id filter parameter', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bookings?driver_id=not-a-uuid")->assertStatus(422);
        getJson("/api/v1/transactions/{$transaction->id}/bookings?driver_id=11111111-1111-1111-1111-111111111111")->assertStatus(422);
    });

    test('filters upcoming bookings', function () {
        Carbon::setTestNow('2026-04-24 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $previousBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-10 09:00:00',
            'end_date' => '2026-04-12 09:00:00',
            'note' => 'Previous booking',
        ]);

        $upcomingBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-25 09:00:00',
            'end_date' => '2026-04-27 09:00:00',
            'note' => 'Upcoming booking',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings?status=upcoming");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $upcomingBooking->id);

        expect($response->json('data.*.id'))->not->toContain($previousBooking->id);
    });

    test('filters previous bookings', function () {
        Carbon::setTestNow('2026-04-24 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $previousBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-10 09:00:00',
            'end_date' => '2026-04-12 09:00:00',
            'note' => 'Previous booking',
        ]);

        $upcomingBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-25 09:00:00',
            'end_date' => '2026-04-27 09:00:00',
            'note' => 'Upcoming booking',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings?status=previous");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $previousBooking->id);

        expect($response->json('data.*.id'))->not->toContain($upcomingBooking->id);
    });

    test('filters bookings within the current week', function () {
        Carbon::setTestNow('2026-04-24 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $weeklyBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-22 09:00:00',
            'end_date' => '2026-04-25 09:00:00',
            'note' => 'Weekly booking',
        ]);

        $monthlyOnlyBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-29 09:00:00',
            'end_date' => '2026-04-30 09:00:00',
            'note' => 'Monthly booking',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings?period=week");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $weeklyBooking->id);

        expect($response->json('data.*.id'))->not->toContain($monthlyOnlyBooking->id);
    });

    test('filters bookings within the current month', function () {
        Carbon::setTestNow('2026-04-24 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();

        $monthlyBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-29 09:00:00',
            'end_date' => '2026-04-30 09:00:00',
            'note' => 'Monthly booking',
        ]);

        $outsideMonthBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-05-02 09:00:00',
            'end_date' => '2026-05-03 09:00:00',
            'note' => 'Outside month booking',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings?period=month");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $monthlyBooking->id);

        expect($response->json('data.*.id'))->not->toContain($outsideMonthBooking->id);
    });

    test('filters bookings by car and driver scope', function () {
        Carbon::setTestNow('2026-04-24 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $otherCar = Car::factory()->create();
        $driver = Driver::factory()->create();
        $otherDriver = Driver::factory()->create();

        $matchingBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-25 09:00:00',
            'end_date' => '2026-04-27 09:00:00',
            'note' => 'Matching booking',
        ]);

        $transaction->bookings()->create([
            'car_id' => $otherCar->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-25 09:00:00',
            'end_date' => '2026-04-27 09:00:00',
            'note' => 'Different car booking',
        ]);

        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $otherDriver->id,
            'start_date' => '2026-04-25 09:00:00',
            'end_date' => '2026-04-27 09:00:00',
            'note' => 'Different driver booking',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings?status=upcoming&car_id={$car->id}&driver_id={$driver->id}");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingBooking->id);
    });

    test('filters upcoming bookings by car', function () {
        Carbon::setTestNow('2026-04-24 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $otherCar = Car::factory()->create();
        $driver = Driver::factory()->create();

        $matchingBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-25 09:00:00',
            'end_date' => '2026-04-27 09:00:00',
            'note' => 'Upcoming target car booking',
        ]);

        $transaction->bookings()->create([
            'car_id' => $otherCar->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-25 09:00:00',
            'end_date' => '2026-04-27 09:00:00',
            'note' => 'Upcoming other car booking',
        ]);

        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-10 09:00:00',
            'end_date' => '2026-04-12 09:00:00',
            'note' => 'Previous target car booking',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings?status=upcoming&car_id={$car->id}");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingBooking->id);
    });

    test('filters previous bookings by driver', function () {
        Carbon::setTestNow('2026-04-24 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $otherDriver = Driver::factory()->create();

        $matchingBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-10 09:00:00',
            'end_date' => '2026-04-12 09:00:00',
            'note' => 'Previous target driver booking',
        ]);

        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $otherDriver->id,
            'start_date' => '2026-04-10 09:00:00',
            'end_date' => '2026-04-12 09:00:00',
            'note' => 'Previous other driver booking',
        ]);

        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-25 09:00:00',
            'end_date' => '2026-04-27 09:00:00',
            'note' => 'Upcoming target driver booking',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings?status=previous&driver_id={$driver->id}");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingBooking->id);
    });

    test('filters weekly bookings by driver', function () {
        Carbon::setTestNow('2026-04-24 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $driver = Driver::factory()->create();
        $otherDriver = Driver::factory()->create();

        $matchingBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-22 09:00:00',
            'end_date' => '2026-04-23 09:00:00',
            'note' => 'Weekly target driver booking',
        ]);

        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $otherDriver->id,
            'start_date' => '2026-04-22 09:00:00',
            'end_date' => '2026-04-23 09:00:00',
            'note' => 'Weekly other driver booking',
        ]);

        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-29 09:00:00',
            'end_date' => '2026-04-30 09:00:00',
            'note' => 'Monthly only target driver booking',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings?period=week&driver_id={$driver->id}");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingBooking->id);
    });

    test('filters monthly bookings by car', function () {
        Carbon::setTestNow('2026-04-24 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $otherCar = Car::factory()->create();
        $driver = Driver::factory()->create();

        $matchingBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-29 09:00:00',
            'end_date' => '2026-04-30 09:00:00',
            'note' => 'Monthly target car booking',
        ]);

        $transaction->bookings()->create([
            'car_id' => $otherCar->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-29 09:00:00',
            'end_date' => '2026-04-30 09:00:00',
            'note' => 'Monthly other car booking',
        ]);

        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-05-02 09:00:00',
            'end_date' => '2026-05-03 09:00:00',
            'note' => 'Outside month target car booking',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings?period=month&car_id={$car->id}");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingBooking->id);
    });

    test('combines status period and car scope filters', function () {
        Carbon::setTestNow('2026-04-24 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $car = Car::factory()->create();
        $otherCar = Car::factory()->create();
        $driver = Driver::factory()->create();

        $matchingBooking = $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-25 09:00:00',
            'end_date' => '2026-04-26 09:00:00',
            'note' => 'Upcoming weekly target car booking',
        ]);

        $transaction->bookings()->create([
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-29 09:00:00',
            'end_date' => '2026-04-30 09:00:00',
            'note' => 'Upcoming monthly only target car booking',
        ]);

        $transaction->bookings()->create([
            'car_id' => $otherCar->id,
            'driver_id' => $driver->id,
            'start_date' => '2026-04-25 09:00:00',
            'end_date' => '2026-04-26 09:00:00',
            'note' => 'Upcoming weekly other car booking',
        ]);

        $response = getJson("/api/v1/transactions/{$transaction->id}/bookings?status=upcoming&period=week&car_id={$car->id}");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingBooking->id);
    });
});
