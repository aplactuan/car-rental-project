<?php

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function purchaseOrderPayload(array $overrides = []): array
{
    return array_merge([
        'customer_id' => $overrides['customer_id'] ?? Customer::factory()->create()->id,
        'po_number' => 'PO-1001',
        'date' => '2026-07-15',
        'amount' => 250000,
        'request_person' => 'Jane Doe',
        'description' => 'Airport transfer package',
    ], $overrides);
}

describe('guest user', function () {
    test('it cannot add a purchase order if user is not logged in', function () {
        postJson('/api/v1/purchase-orders', purchaseOrderPayload())->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Storage::fake('public');
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can add a purchase order thru api', function () {
        $customer = Customer::factory()->create();
        $payload = purchaseOrderPayload(['customer_id' => $customer->id]);

        $response = postJson('/api/v1/purchase-orders', $payload);

        assertDatabaseHas('purchase_orders', [
            'customer_id' => $customer->id,
            'po_number' => $payload['po_number'],
            'amount' => $payload['amount'],
            'request_person' => $payload['request_person'],
            'description' => $payload['description'],
            'status' => 'pending',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'createdAt',
                        'poNumber',
                        'date',
                        'amount',
                        'requestPerson',
                        'description',
                        'status',
                        'customerId',
                        'attachments',
                    ],
                    'relationships' => [
                        'customer',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', 'purchase-order')
            ->assertJsonPath('data.attributes.poNumber', $payload['po_number'])
            ->assertJsonPath('data.attributes.date', $payload['date'])
            ->assertJsonPath('data.attributes.amount', $payload['amount'])
            ->assertJsonPath('data.attributes.requestPerson', $payload['request_person'])
            ->assertJsonPath('data.attributes.description', $payload['description'])
            ->assertJsonPath('data.attributes.status', 'pending')
            ->assertJsonPath('data.attributes.customerId', $customer->id)
            ->assertJsonPath('data.attributes.attachments', [])
            ->assertJsonPath('data.relationships.customer.data.id', $customer->id)
            ->assertJsonPath('data.relationships.customer.data.attributes.name', $customer->name);
    });

    test('it can add a purchase order with ok status', function () {
        $customer = Customer::factory()->create();

        postJson('/api/v1/purchase-orders', purchaseOrderPayload([
            'customer_id' => $customer->id,
            'status' => 'ok',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.attributes.status', 'ok');

        assertDatabaseHas('purchase_orders', [
            'po_number' => 'PO-1001',
            'status' => 'ok',
        ]);
    });

    test('it rejects an invalid purchase order status', function () {
        $customer = Customer::factory()->create();

        postJson('/api/v1/purchase-orders', purchaseOrderPayload([
            'customer_id' => $customer->id,
            'status' => 'approved',
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/status');
    });

    test('it can add a purchase order with multiple attachments', function () {
        $customer = Customer::factory()->create();
        $payload = purchaseOrderPayload([
            'customer_id' => $customer->id,
            'attachments' => [
                UploadedFile::fake()->image('quote.jpg'),
                UploadedFile::fake()->image('scan.png'),
                UploadedFile::fake()->createWithContent('spec.pdf', '%PDF-1.4 fake'),
            ],
        ]);

        $response = postJson('/api/v1/purchase-orders', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.attributes.poNumber', $payload['po_number']);

        expect($response->json('data.attributes.attachments'))->toHaveCount(3)
            ->and($response->json('data.attributes.attachments.0.fileName'))->toBe('quote.jpg')
            ->and($response->json('data.attributes.attachments.0.url'))->not->toBeEmpty()
            ->and($response->json('data.attributes.attachments.0.id'))->not->toBeEmpty();

        $purchaseOrder = PurchaseOrder::query()->where('po_number', $payload['po_number'])->firstOrFail();
        expect($purchaseOrder->getMedia(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION))->toHaveCount(3);
    });

    test('it rejects invalid purchase order attachment types', function () {
        $customer = Customer::factory()->create();

        postJson('/api/v1/purchase-orders', purchaseOrderPayload([
            'customer_id' => $customer->id,
            'attachments' => [
                UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
            ],
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/attachments/0');
    });

    test('it can add a purchase order without request person and description', function () {
        $customer = Customer::factory()->create();

        $response = postJson('/api/v1/purchase-orders', purchaseOrderPayload([
            'customer_id' => $customer->id,
            'request_person' => null,
            'description' => null,
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.attributes.requestPerson', null)
            ->assertJsonPath('data.attributes.description', null);
    });

    test('it fails to add a purchase order with a duplicate po number', function () {
        $customer = Customer::factory()->create();
        PurchaseOrder::factory()->forCustomer($customer)->create(['po_number' => 'PO-DUPLICATE']);

        postJson('/api/v1/purchase-orders', purchaseOrderPayload([
            'customer_id' => $customer->id,
            'po_number' => 'PO-DUPLICATE',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/po_number');
    });

    test('it fails to add a purchase order with an invalid customer', function () {
        postJson('/api/v1/purchase-orders', purchaseOrderPayload([
            'customer_id' => '00000000-0000-0000-0000-000000000000',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/customer_id');
    });

    test('it fails to add a purchase order with a negative amount', function () {
        postJson('/api/v1/purchase-orders', purchaseOrderPayload(['amount' => -1]))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/amount');
    });
});
