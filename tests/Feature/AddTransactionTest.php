<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot create a transaction when not authenticated', function () {
        $customer = Customer::factory()->create();
        postJson('/api/v1/transactions', [
            'customer_id' => $customer->id,
            'name' => 'Test',
        ])->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can create a transaction', function () {
        $customer = Customer::factory()->create(['name' => 'Jane Smith']);
        $payload = [
            'customer_id' => $customer->id,
            'name' => 'Corporate fleet',
        ];

        $response = postJson('/api/v1/transactions', $payload);

        $response->assertStatus(201);
        $json = $response->json();
        expect($json)->toHaveKey('data');
        $data = $json['data'];
        expect($data)->toHaveKeys(['type', 'id', 'attributes', 'relationships']);
        expect($data['type'])->toBe('transaction');
        expect($data['attributes']['userId'])->toBe($this->user->id);
        expect($data['attributes']['customerId'])->toBe($customer->id);
        expect($data['attributes']['name'])->toBe('Corporate fleet');

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'name' => 'Corporate fleet',
        ]);
    });

    test('returns 422 when customer_id is missing', function () {
        postJson('/api/v1/transactions', ['name' => 'Only name'])->assertStatus(422);
    });

    test('returns 422 when name is missing', function () {
        $customer = Customer::factory()->create();
        postJson('/api/v1/transactions', ['customer_id' => $customer->id])->assertStatus(422);
    });
});
