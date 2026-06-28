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
        getJson('/api/v1/bookings')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
        $this->car = Car::factory()->create();
        $this->driver = Driver::factory()->create();
    });

    afterEach(function () {
        Carbon::setTestNow();
    });

    test('returns paginated bookings for the authenticated user', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-05-01 09:00:00',
            'end_date' => '2026-05-03 09:00:00',
        ]);
        $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-05-10 09:00:00',
            'end_date' => '2026-05-12 09:00:00',
        ]);

        $response = getJson('/api/v1/bookings');

        $response->assertSuccessful()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'booking');
    });

    test('does not return bookings from another users transactions', function () {
        $otherUser = User::factory()->create();
        $otherTransaction = Transaction::factory()->create(['user_id' => $otherUser->id]);
        $otherTransaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-05-01 09:00:00',
            'end_date' => '2026-05-03 09:00:00',
        ]);

        $response = getJson('/api/v1/bookings');

        $response->assertSuccessful()->assertJsonCount(0, 'data');
    });

    test('returns empty list when user has no bookings', function () {
        getJson('/api/v1/bookings')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    test('respects per_page pagination and includes meta', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        foreach (range(1, 5) as $i) {
            $transaction->bookings()->create([
                'car_id' => $this->car->id,
                'driver_id' => $this->driver->id,
                'price' => 500,
                'start_date' => "2026-05-0{$i} 09:00:00",
                'end_date' => "2026-05-0{$i} 18:00:00",
            ]);
        }

        $response = getJson('/api/v1/bookings?per_page=2');

        $response->assertSuccessful()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    });

    test('validates per_page minimum', function () {
        getJson('/api/v1/bookings?per_page=0')->assertUnprocessable();
    });

    test('validates per_page maximum', function () {
        getJson('/api/v1/bookings?per_page=101')->assertUnprocessable();
    });

    test('validates status filter rejects unknown values', function () {
        getJson('/api/v1/bookings?status=upcoming')->assertUnprocessable();
        getJson('/api/v1/bookings?status=previous')->assertUnprocessable();
        getJson('/api/v1/bookings?status=invalid')->assertUnprocessable();
    });

    test('accepts all four valid status values', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        getJson('/api/v1/bookings?status=completed')->assertSuccessful();
        getJson('/api/v1/bookings?status=today')->assertSuccessful();
        getJson('/api/v1/bookings?status=ongoing')->assertSuccessful();
        getJson('/api/v1/bookings?status=incoming')->assertSuccessful();
    });

    test('passes validated filters to the repository', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        $repository = Mockery::mock(BookingRepositoryInterface::class);
        $repository->shouldReceive('getAllByUser')
            ->once()
            ->with($this->user->id, ['status' => 'incoming'], 15)
            ->andReturn(collect());

        app()->instance(BookingRepositoryInterface::class, $repository);

        getJson('/api/v1/bookings?status=incoming')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    test('passes linked driver filters to the repository', function () {
        $driverUser = User::factory()->create();
        $linkedDriver = Driver::factory()->forUser($driverUser)->create();
        Sanctum::actingAs($driverUser);

        $repository = Mockery::mock(BookingRepositoryInterface::class);
        $repository->shouldReceive('getAllByDriver')
            ->once()
            ->with($linkedDriver->id, ['status' => 'incoming'], 15)
            ->andReturn(collect());
        $repository->shouldNotReceive('getAllByUser');

        app()->instance(BookingRepositoryInterface::class, $repository);

        getJson('/api/v1/bookings?status=incoming')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    test('filters completed bookings', function () {
        Carbon::setTestNow('2026-05-29 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        $completedBooking = $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-05-25 09:00:00',
            'end_date' => '2026-05-27 09:00:00',
        ]);

        $incomingBooking = $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-06-01 09:00:00',
            'end_date' => '2026-06-03 09:00:00',
        ]);

        $response = getJson('/api/v1/bookings?status=completed');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $completedBooking->id);

        expect($response->json('data.*.id'))->not->toContain($incomingBooking->id);
    });

    test('filters today bookings', function () {
        Carbon::setTestNow('2026-05-29 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        $todayBooking = $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-05-29 08:00:00',
            'end_date' => '2026-05-29 18:00:00',
        ]);

        $otherDayBooking = $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-05-30 09:00:00',
            'end_date' => '2026-05-31 09:00:00',
        ]);

        $response = getJson('/api/v1/bookings?status=today');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $todayBooking->id);

        expect($response->json('data.*.id'))->not->toContain($otherDayBooking->id);
    });

    test('filters ongoing bookings', function () {
        Carbon::setTestNow('2026-05-29 12:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        $ongoingBooking = $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-05-29 08:00:00',
            'end_date' => '2026-05-29 18:00:00',
        ]);

        $completedBooking = $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-05-27 08:00:00',
            'end_date' => '2026-05-28 18:00:00',
        ]);

        $incomingBooking = $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-05-30 08:00:00',
            'end_date' => '2026-05-31 18:00:00',
        ]);

        $response = getJson('/api/v1/bookings?status=ongoing');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ongoingBooking->id);

        expect($response->json('data.*.id'))->not->toContain($completedBooking->id);
        expect($response->json('data.*.id'))->not->toContain($incomingBooking->id);
    });

    test('filters incoming bookings', function () {
        Carbon::setTestNow('2026-05-29 10:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        $incomingBooking = $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-06-01 09:00:00',
            'end_date' => '2026-06-03 09:00:00',
        ]);

        $completedBooking = $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-05-25 09:00:00',
            'end_date' => '2026-05-27 09:00:00',
        ]);

        $response = getJson('/api/v1/bookings?status=incoming');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $incomingBooking->id);

        expect($response->json('data.*.id'))->not->toContain($completedBooking->id);
    });

    test('ongoing excludes bookings that ended exactly at now', function () {
        Carbon::setTestNow('2026-05-29 12:00:00');

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        $endedNowBooking = $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-05-29 08:00:00',
            'end_date' => '2026-05-29 11:59:59',
        ]);

        $response = getJson('/api/v1/bookings?status=ongoing');

        $response->assertSuccessful()->assertJsonCount(0, 'data');
    });

    test('booking resource includes car and driver relationships', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'price' => 500,
            'start_date' => '2026-06-01 09:00:00',
            'end_date' => '2026-06-03 09:00:00',
        ]);

        $response = getJson('/api/v1/bookings');

        $response->assertSuccessful()
            ->assertJsonPath('data.0.relationships.car.data.id', $this->car->id)
            ->assertJsonPath('data.0.relationships.driver.data.id', $this->driver->id);
    });

    test('linked driver users only see their assigned bookings', function () {
        $driverUser = User::factory()->create();
        $linkedDriver = Driver::factory()->forUser($driverUser)->create();
        $otherDriver = Driver::factory()->create();
        Sanctum::actingAs($driverUser);

        $firstTransaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $secondTransaction = Transaction::factory()->create(['user_id' => User::factory()->create()->id]);

        $assignedBooking = $firstTransaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $linkedDriver->id,
            'price' => 500,
            'start_date' => '2026-06-01 09:00:00',
            'end_date' => '2026-06-03 09:00:00',
        ]);

        $secondTransaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $otherDriver->id,
            'price' => 500,
            'start_date' => '2026-06-05 09:00:00',
            'end_date' => '2026-06-07 09:00:00',
        ]);

        getJson('/api/v1/bookings')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignedBooking->id);
    });

    test('linked driver users still honor status and pagination', function () {
        Carbon::setTestNow('2026-05-29 10:00:00');

        $driverUser = User::factory()->create();
        $linkedDriver = Driver::factory()->forUser($driverUser)->create();
        $otherDriver = Driver::factory()->create();
        Sanctum::actingAs($driverUser);

        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        $firstIncomingBooking = $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $linkedDriver->id,
            'price' => 500,
            'start_date' => '2026-06-01 09:00:00',
            'end_date' => '2026-06-02 09:00:00',
        ]);

        $secondIncomingBooking = $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $linkedDriver->id,
            'price' => 500,
            'start_date' => '2026-06-03 09:00:00',
            'end_date' => '2026-06-04 09:00:00',
        ]);

        $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $linkedDriver->id,
            'price' => 500,
            'start_date' => '2026-05-20 09:00:00',
            'end_date' => '2026-05-21 09:00:00',
        ]);

        $transaction->bookings()->create([
            'car_id' => $this->car->id,
            'driver_id' => $otherDriver->id,
            'price' => 500,
            'start_date' => '2026-06-06 09:00:00',
            'end_date' => '2026-06-07 09:00:00',
        ]);

        $response = getJson('/api/v1/bookings?status=incoming&per_page=1');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1);

        expect($response->json('data.*.id'))
            ->toContain($secondIncomingBooking->id)
            ->not->toContain($firstIncomingBooking->id);
    });
});
