<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\postJson;

test('user can login via api', function () {
    User::factory()->create([
        'email' => 'tester@test.com',
        'password' => Hash::make('password1234')
    ]);

    postJson('api/login', [
        'username' => 'tester@test.com',
        'password' => 'password1234'
    ])->assertStatus(201)
    ->assertJsonStructure();
});
