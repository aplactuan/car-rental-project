<?php

use App\Models\Customer;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot list programs if user is not logged in', function () {
        getJson('/api/v1/programs')->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can list programs with default pagination', function () {
        Program::factory()->count(3)->create();

        getJson('/api/v1/programs')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'createdAt',
                        'attributes' => [
                            'name',
                            'description',
                            'customerId',
                        ],
                        'relationships' => [
                            'customer',
                        ],
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.type', 'program');
    });

    test('it respects per_page pagination parameter', function () {
        Program::factory()->count(5)->create();

        getJson('/api/v1/programs?per_page=2')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    });

    test('it validates per_page parameter', function () {
        getJson('/api/v1/programs?per_page=0')
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/per_page');
    });

    test('it does not include customer name on list responses', function () {
        $customer = Customer::factory()->create(['name' => 'Acme Travel']);
        Program::factory()->forCustomer($customer)->create(['name' => 'List Program']);

        $response = getJson('/api/v1/programs');

        $program = collect($response->json('data'))
            ->firstWhere('attributes.name', 'List Program');

        expect($program)->not->toBeNull();
        expect($program['relationships']['customer']['data']['id'])->toBe($customer->id);
        expect($program['relationships']['customer']['data'])->not->toHaveKey('attributes');
    });

    test('it can filter programs by customer', function () {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $matching = Program::factory()->forCustomer($customer)->create(['name' => 'Match']);
        Program::factory()->forCustomer($otherCustomer)->create(['name' => 'Other']);

        getJson("/api/v1/programs?customer_id={$customer->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.attributes.customerId', $customer->id);
    });

    test('it validates customer_id filter', function () {
        getJson('/api/v1/programs?customer_id=not-a-uuid')
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/customer_id');
    });
});
