<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('user login test', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'email' => 'tester@test.com',
            'password' => Hash::make('password1234')
        ]);
    });

    test('cannot access if credential is invalid', function () {
        postJson('/api/login', [
            'email' => 'tester@test.com',
            'password' => '23423423423234'
        ])->assertStatus(401);
    });

    test('user can login via api', function () {
        postJson('/api/login', [
            'email' => 'tester@test.com',
            'password' => 'password1234'
        ])->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token'
                ]
            ]);
    });
});
