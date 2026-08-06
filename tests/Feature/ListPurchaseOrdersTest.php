<?php

use App\Models\Customer;
use App\Models\Program;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot list purchase orders if user is not logged in', function () {
        getJson('/api/v1/purchase-orders')->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can list purchase orders with default pagination', function () {
        PurchaseOrder::factory()->count(3)->create();

        $response = getJson('/api/v1/purchase-orders');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'createdAt',
                        'attributes' => [
                            'poNumber',
                            'date',
                            'amount',
                            'customerId',
                        ],
                        'relationships' => [
                            'customer',
                            'program',
                        ],
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.type', 'purchase-order');
    });

    test('it respects per_page pagination parameter', function () {
        PurchaseOrder::factory()->count(5)->create();

        $response = getJson('/api/v1/purchase-orders?per_page=2');

        $response
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    });

    test('it validates per_page parameter', function () {
        $response = getJson('/api/v1/purchase-orders?per_page=0');

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/per_page');
    });

    test('it does not include customer name on list responses', function () {
        $customer = Customer::factory()->create(['name' => 'Acme Travel']);
        PurchaseOrder::factory()->forCustomer($customer)->create(['po_number' => 'PO-LIST-1']);

        $response = getJson('/api/v1/purchase-orders');

        $purchaseOrder = collect($response->json('data'))
            ->firstWhere('attributes.poNumber', 'PO-LIST-1');

        expect($purchaseOrder)->not->toBeNull();
        expect($purchaseOrder['relationships']['customer']['data']['id'])->toBe($customer->id);
        expect($purchaseOrder['relationships']['customer']['data'])->not->toHaveKey('attributes');
    });

    test('it does not include program name on list responses', function () {
        $program = Program::factory()->create(['name' => 'Tourism Drive']);
        PurchaseOrder::factory()->forProgram($program)->create(['po_number' => 'PO-LIST-PROGRAM']);

        $response = getJson('/api/v1/purchase-orders');

        $purchaseOrder = collect($response->json('data'))
            ->firstWhere('attributes.poNumber', 'PO-LIST-PROGRAM');

        expect($purchaseOrder)->not->toBeNull();
        expect($purchaseOrder['relationships']['program']['data']['id'])->toBe($program->id);
        expect($purchaseOrder['relationships']['program']['data'])->not->toHaveKey('attributes');
    });

    test('it can filter purchase orders by customer', function () {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $matching = PurchaseOrder::factory()->forCustomer($customer)->create(['po_number' => 'PO-MATCH']);
        PurchaseOrder::factory()->forCustomer($otherCustomer)->create(['po_number' => 'PO-OTHER']);

        $response = getJson("/api/v1/purchase-orders?customer_id={$customer->id}");

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.attributes.customerId', $customer->id);
    });

    test('it can filter purchase orders by program', function () {
        $program = Program::factory()->create();
        $otherProgram = Program::factory()->create();

        $matching = PurchaseOrder::factory()->forProgram($program)->create(['po_number' => 'PO-PROGRAM-MATCH']);
        PurchaseOrder::factory()->forProgram($otherProgram)->create(['po_number' => 'PO-PROGRAM-OTHER']);

        getJson("/api/v1/purchase-orders?program_id={$program->id}")
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.attributes.programId', $program->id);
    });

    test('it can filter unprogrammed purchase orders', function () {
        $program = Program::factory()->create();
        $customer = Customer::factory()->create();

        $unprogrammed = PurchaseOrder::factory()->forCustomer($customer)->create([
            'po_number' => 'PO-UNPROGRAMMED',
            'program_id' => null,
        ]);
        PurchaseOrder::factory()->forCustomer($customer)->forProgram($program)->create([
            'po_number' => 'PO-HAS-PROGRAM',
        ]);
        PurchaseOrder::factory()->create([
            'po_number' => 'PO-OTHER-CUSTOMER-UNPROGRAMMED',
            'program_id' => null,
        ]);

        getJson("/api/v1/purchase-orders?customer_id={$customer->id}&unprogrammed=1&per_page=100")
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unprogrammed->id)
            ->assertJsonPath('data.0.attributes.programId', null)
            ->assertJsonPath('data.0.relationships.program.data', null);
    });

    test('it treats unprogrammed false as no program filter', function () {
        $program = Program::factory()->create();

        PurchaseOrder::factory()->forProgram($program)->create(['po_number' => 'PO-WITH-PROGRAM']);
        PurchaseOrder::factory()->create(['po_number' => 'PO-WITHOUT-PROGRAM', 'program_id' => null]);

        getJson('/api/v1/purchase-orders?unprogrammed=0&per_page=100')
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    test('it rejects unprogrammed together with program_id', function () {
        $program = Program::factory()->create();

        getJson("/api/v1/purchase-orders?unprogrammed=1&program_id={$program->id}")
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/unprogrammed')
            ->assertJsonPath('errors.0.detail', 'Cannot use unprogrammed together with program_id.');
    });

    test('it validates unprogrammed filter', function () {
        getJson('/api/v1/purchase-orders?unprogrammed=notabool')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/unprogrammed');
    });

    test('it validates customer_id filter', function () {
        getJson('/api/v1/purchase-orders?customer_id=not-a-uuid')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/customer_id');
    });

    test('it validates program_id filter', function () {
        getJson('/api/v1/purchase-orders?program_id=not-a-uuid')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/program_id');
    });
});
