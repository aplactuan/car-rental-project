<?php

use App\Models\Customer;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot update a program if user is not logged in', function () {
        $program = Program::factory()->create();

        putJson("/api/v1/programs/{$program->id}", [
            'name' => 'Updated Program',
        ])->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can update a program through api', function () {
        $program = Program::factory()->create([
            'name' => 'Original Program',
            'description' => 'Original description',
        ]);

        $payload = [
            'name' => 'Updated Program',
            'description' => 'Updated description',
        ];

        putJson("/api/v1/programs/{$program->id}", $payload)
            ->assertStatus(200)
            ->assertJsonPath('data.type', 'program')
            ->assertJsonPath('data.attributes.name', $payload['name'])
            ->assertJsonPath('data.attributes.description', $payload['description']);

        assertDatabaseHas('programs', [
            'id' => $program->id,
            'name' => $payload['name'],
            'description' => $payload['description'],
        ]);
    });

    test('it can reassign a program to another customer', function () {
        $originalCustomer = Customer::factory()->create();
        $newCustomer = Customer::factory()->create();
        $program = Program::factory()->forCustomer($originalCustomer)->create();

        putJson("/api/v1/programs/{$program->id}", [
            'customer_id' => $newCustomer->id,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.customerId', $newCustomer->id)
            ->assertJsonPath('data.relationships.customer.data.id', $newCustomer->id)
            ->assertJsonPath('data.relationships.customer.data.attributes.name', $newCustomer->name);

        assertDatabaseHas('programs', [
            'id' => $program->id,
            'customer_id' => $newCustomer->id,
        ]);
    });

    test('it can clear a program description', function () {
        $program = Program::factory()->create([
            'description' => 'Something',
        ]);

        putJson("/api/v1/programs/{$program->id}", [
            'description' => null,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.description', null);
    });

    test('it fails to update a program with an invalid customer', function () {
        $program = Program::factory()->create();

        putJson("/api/v1/programs/{$program->id}", [
            'customer_id' => '00000000-0000-0000-0000-000000000000',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/customer_id');
    });
});
