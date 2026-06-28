<?php

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('user login test', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'email' => 'tester@test.com',
            'password' => Hash::make('password1234'),
        ]);
    });

    test('cannot access if credential is invalid', function () {
        postJson('/api/login', [
            'email' => 'tester@test.com',
            'password' => '23423423423234',
        ])->assertStatus(401);
    });

    test('user can login via api', function () {
        postJson('/api/login', [
            'email' => 'tester@test.com',
            'password' => 'password1234',
        ])->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'role',
                    'user',
                ],
            ]);
    });

    test('login returns driver role for linked driver users', function () {
        $driverUser = User::factory()->create([
            'email' => 'driver@test.com',
            'password' => Hash::make('password1234'),
        ]);
        Driver::factory()->forUser($driverUser)->create();

        postJson('/api/login', [
            'email' => 'driver@test.com',
            'password' => 'password1234',
        ])->assertSuccessful()
            ->assertJsonPath('data.role', 'driver')
            ->assertJsonPath('data.user.attributes.role', 'driver');
    });

    test('authenticated user endpoint returns resolved driver role', function () {
        $driverUser = User::factory()->create();
        Driver::factory()->forUser($driverUser)->create();
        Sanctum::actingAs($driverUser);

        getJson('/api/user')
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'user')
            ->assertJsonPath('data.attributes.role', 'driver');
    });
});
