<?php

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

function linkedDriverPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'license_number' => 'LIC-'.uniqid(),
        'license_expiry_date' => '2030-01-01',
        'address' => '123 Main St',
        'phone_number' => '+15555550123',
    ], $overrides);
}

describe('driver user link', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        Sanctum::actingAs($this->admin);
    });

    test('it can create a driver linked to a user account', function () {
        $driverUser = User::factory()->create();

        $payload = linkedDriverPayload(['user_id' => $driverUser->id]);

        postJson('/api/v1/drivers', $payload)
            ->assertCreated()
            ->assertJsonPath('data.attributes.userId', $driverUser->id);

        assertDatabaseHas('drivers', [
            'user_id' => $driverUser->id,
            'license_number' => $payload['license_number'],
        ]);
    });

    test('it creates a driver without a linked user by default', function () {
        $payload = linkedDriverPayload();

        postJson('/api/v1/drivers', $payload)
            ->assertCreated()
            ->assertJsonPath('data.attributes.userId', null);

        assertDatabaseHas('drivers', [
            'license_number' => $payload['license_number'],
            'user_id' => null,
        ]);
    });

    test('it rejects linking a user account that is already linked to another driver', function () {
        $driverUser = User::factory()->create();
        Driver::factory()->forUser($driverUser)->create();

        $payload = linkedDriverPayload(['user_id' => $driverUser->id]);

        postJson('/api/v1/drivers', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/user_id');
    });

    test('it can link a user account when updating a driver', function () {
        $driver = Driver::factory()->create();
        $driverUser = User::factory()->create();

        putJson("/api/v1/drivers/{$driver->id}", [
            'user_id' => $driverUser->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.userId', $driverUser->id);

        expect($driver->fresh()->user_id)->toBe($driverUser->id);
    });

    test('it can unlink a user account when updating a driver', function () {
        $driverUser = User::factory()->create();
        $driver = Driver::factory()->forUser($driverUser)->create();

        putJson("/api/v1/drivers/{$driver->id}", [
            'user_id' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.userId', null);

        expect($driver->fresh()->user_id)->toBeNull();
    });

    test('it exposes the driver relationship on the user model', function () {
        $driverUser = User::factory()->create();
        $driver = Driver::factory()->forUser($driverUser)->create();

        expect($driverUser->fresh()->driver?->is($driver))->toBeTrue()
            ->and($driver->fresh()->user?->is($driverUser))->toBeTrue();
    });

    test('it ignores user_id changes from a linked driver user', function () {
        $driverUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $driver = Driver::factory()->forUser($driverUser)->create();

        Sanctum::actingAs($driverUser);

        putJson("/api/v1/drivers/{$driver->id}", [
            'user_id' => $otherUser->id,
            'first_name' => 'Updated',
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.firstName', 'Updated')
            ->assertJsonPath('data.attributes.userId', $driverUser->id);

        expect($driver->fresh()->user_id)->toBe($driverUser->id);
    });
});
