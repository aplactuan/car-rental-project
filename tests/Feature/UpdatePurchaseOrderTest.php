<?php

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot update a purchase order if user is not logged in', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", [
            'amount' => 300000,
        ])->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Storage::fake('public');
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can update a purchase order through api', function () {
        $customer = Customer::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create([
            'po_number' => 'PO-OLD',
            'amount' => 100000,
        ]);

        $payload = [
            'po_number' => 'PO-NEW',
            'date' => '2026-08-01',
            'amount' => 325000,
            'request_person' => 'Updated Person',
            'description' => 'Updated description',
            'status' => 'ok',
        ];

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", $payload)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'poNumber',
                        'date',
                        'amount',
                        'requestPerson',
                        'description',
                        'status',
                        'attachments',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', 'purchase-order')
            ->assertJsonPath('data.attributes.poNumber', $payload['po_number'])
            ->assertJsonPath('data.attributes.date', $payload['date'])
            ->assertJsonPath('data.attributes.amount', $payload['amount'])
            ->assertJsonPath('data.attributes.requestPerson', $payload['request_person'])
            ->assertJsonPath('data.attributes.description', $payload['description'])
            ->assertJsonPath('data.attributes.status', 'ok')
            ->assertJsonPath('data.attributes.attachments', []);

        assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'po_number' => $payload['po_number'],
            'amount' => $payload['amount'],
            'request_person' => $payload['request_person'],
            'description' => $payload['description'],
            'status' => 'ok',
        ]);
    });

    test('it rejects an invalid purchase order status on update', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", [
            'status' => 'approved',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/status');
    });

    test('it can add and remove purchase order attachments on update', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $purchaseOrder->addMedia(UploadedFile::fake()->image('old.jpg'))
            ->toMediaCollection(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION);
        $purchaseOrder->addMedia(UploadedFile::fake()->createWithContent('keep.pdf', '%PDF-1.4 fake'))
            ->toMediaCollection(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION);

        $oldAttachmentId = $purchaseOrder->getFirstMedia(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION)->uuid;

        $response = putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", [
            'remove_attachment_ids' => [$oldAttachmentId],
            'attachments' => [
                UploadedFile::fake()->image('new.png'),
            ],
        ]);

        $response->assertOk();

        $attachments = collect($response->json('data.attributes.attachments'));

        expect($attachments)->toHaveCount(2)
            ->and($attachments->pluck('fileName')->all())->toContain('keep.pdf', 'new.png')
            ->and($attachments->pluck('id')->all())->not->toContain($oldAttachmentId);

        expect($purchaseOrder->fresh()->getMedia(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION))->toHaveCount(2);
    });

    test('it rejects removing attachments that do not belong to the purchase order', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $otherPurchaseOrder = PurchaseOrder::factory()->create();
        $otherPurchaseOrder->addMedia(UploadedFile::fake()->image('other.jpg'))
            ->toMediaCollection(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION);

        $foreignAttachmentId = $otherPurchaseOrder->getFirstMedia(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION)->uuid;

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", [
            'remove_attachment_ids' => [$foreignAttachmentId],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/remove_attachment_ids/0');
    });

    test('it can reassign a purchase order to another customer', function () {
        $originalCustomer = Customer::factory()->create();
        $newCustomer = Customer::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->forCustomer($originalCustomer)->create();

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", [
            'customer_id' => $newCustomer->id,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.customerId', $newCustomer->id)
            ->assertJsonPath('data.relationships.customer.data.id', $newCustomer->id)
            ->assertJsonPath('data.relationships.customer.data.attributes.name', $newCustomer->name);

        assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'customer_id' => $newCustomer->id,
        ]);
    });

    test('it fails to update a purchase order with a duplicate po number', function () {
        $customer = Customer::factory()->create();
        PurchaseOrder::factory()->forCustomer($customer)->create(['po_number' => 'PO-TAKEN']);
        $purchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create(['po_number' => 'PO-OWN']);

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", ['po_number' => 'PO-TAKEN'])
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/po_number');
    });

    test('it allows keeping the same po number on update', function () {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'po_number' => 'PO-KEEP',
            'amount' => 100000,
        ]);

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", [
            'po_number' => 'PO-KEEP',
            'amount' => 200000,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.poNumber', 'PO-KEEP')
            ->assertJsonPath('data.attributes.amount', 200000);
    });
});
