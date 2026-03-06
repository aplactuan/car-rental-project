<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot create a transaction when not authenticated', function () {
        postJson('/api/v1/transactions', ['customer_name' => 'John Doe'])->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can create a transaction', function () {
        $payload = ['customer_name' => 'Jane Smith'];

        $response = postJson('/api/v1/transactions', $payload);

        $response->assertStatus(201);
        $json = $response->json();
        expect($json)->toHaveKey('data');
        $data = $json['data'];
        expect($data)->toHaveKeys(['type', 'id', 'attributes', 'relationships']);
        expect($data['type'])->toBe('transaction');
        expect($data['attributes']['userId'])->toBe($this->user->id);
        expect($data['attributes']['customerName'])->toBe('Jane Smith');

        $this->assertDatabaseHas('transactions', [
            'customer_name' => 'Jane Smith',
        ]);
    });

    test('returns 422 when customer_name is missing', function () {
        postJson('/api/v1/transactions', [])->assertStatus(422);
    });
});
