<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('user can login via api', function () {
    User::factory()->create([
        'email' => 'tester@test.com',
        'password' => Hash::make('password1234')
    ]);

    postJson('/api/login', [
        'username' => 'tester@test.com',
        'password' => 'password1234'
    ])->assertStatus(201)
    ->assertJsonStructure();
});
