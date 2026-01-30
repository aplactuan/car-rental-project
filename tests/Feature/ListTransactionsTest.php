<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot list transactions when not authenticated', function () {
        getJson('/api/v1/transactions')->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can list own transactions', function () {
        Transaction::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = getJson('/api/v1/transactions');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    test('does not see other users transactions', function () {
        $otherUser = User::factory()->create();
        Transaction::factory()->create(['user_id' => $this->user->id]);
        Transaction::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = getJson('/api/v1/transactions');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });
});
