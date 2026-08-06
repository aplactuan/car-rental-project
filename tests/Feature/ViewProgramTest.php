<?php

use App\Models\Customer;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot view a program if user is not logged in', function () {
        $program = Program::factory()->create();

        getJson("/api/v1/programs/{$program->id}")->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can view a program through api', function () {
        $customer = Customer::factory()->create(['name' => 'Acme Travel']);
        $program = Program::factory()->forCustomer($customer)->create([
            'name' => 'Heritage Route',
            'description' => 'City heritage transport coverage',
        ]);

        getJson("/api/v1/programs/{$program->id}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'createdAt',
                        'name',
                        'description',
                        'customerId',
                    ],
                    'relationships' => [
                        'customer',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', 'program')
            ->assertJsonPath('data.id', $program->id)
            ->assertJsonPath('data.attributes.name', 'Heritage Route')
            ->assertJsonPath('data.attributes.description', 'City heritage transport coverage')
            ->assertJsonPath('data.attributes.customerId', $customer->id)
            ->assertJsonPath('data.relationships.customer.data.id', $customer->id)
            ->assertJsonPath('data.relationships.customer.data.attributes.name', $customer->name);
    });
});
