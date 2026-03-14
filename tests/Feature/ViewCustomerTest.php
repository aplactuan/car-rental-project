<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot view a customer if user is not logged in', function () {
        $customer = Customer::factory()->create();
        getJson("/api/v1/customers/{$customer->id}")->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can view a customer through api', function () {
        $customer = Customer::factory()->create(['name' => 'Jane Doe', 'type' => Customer::TYPE_PERSONAL]);

        getJson("/api/v1/customers/{$customer->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [
                'type',
                'id',
                'attributes' => [
                    'createdAt',
                    'name',
                    'type',
                ],
            ]])
            ->assertJsonPath('data.type', 'customer')
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.attributes.name', $customer->name)
            ->assertJsonPath('data.attributes.type', $customer->type);
    });
});
