<?php

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot view a purchase order if user is not logged in', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}")->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can view a purchase order through api', function () {
        $customer = Customer::factory()->create(['name' => 'Acme Travel']);
        $program = Program::factory()->create(['name' => 'Tourism Drive']);
        $purchaseOrder = PurchaseOrder::factory()
            ->forCustomer($customer)
            ->forProgram($program)
            ->create([
                'po_number' => 'PO-VIEW-1',
                'date' => '2026-07-20',
                'amount' => 150000,
                'request_person' => 'John Cruz',
                'description' => 'City tour',
            ]);

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}")
            ->assertStatus(200)
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
                        'customerId',
                        'programId',
                        'invoicedTotal',
                        'invoicePaidTotal',
                        'balance',
                    ],
                    'relationships' => [
                        'customer',
                        'program',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', 'purchase-order')
            ->assertJsonPath('data.id', $purchaseOrder->id)
            ->assertJsonPath('data.attributes.poNumber', 'PO-VIEW-1')
            ->assertJsonPath('data.attributes.date', '2026-07-20')
            ->assertJsonPath('data.attributes.amount', 150000)
            ->assertJsonPath('data.attributes.requestPerson', 'John Cruz')
            ->assertJsonPath('data.attributes.description', 'City tour')
            ->assertJsonPath('data.attributes.programId', $program->id)
            ->assertJsonPath('data.attributes.invoicedTotal', 0)
            ->assertJsonPath('data.attributes.invoicePaidTotal', 0)
            ->assertJsonPath('data.attributes.balance', 150000)
            ->assertJsonPath('data.relationships.customer.data.id', $customer->id)
            ->assertJsonPath('data.relationships.customer.data.attributes.name', $customer->name)
            ->assertJsonPath('data.relationships.program.data.id', $program->id)
            ->assertJsonPath('data.relationships.program.data.attributes.name', $program->name);
    });

    test('it can view a purchase order without a program', function () {
        $purchaseOrder = PurchaseOrder::factory()->create(['program_id' => null]);

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.programId', null)
            ->assertJsonPath('data.relationships.program.data', null);
    });

    test('it returns invoiced, paid, and balance rollups for the purchase order', function () {
        $purchaseOrder = PurchaseOrder::factory()->create(['amount' => 20000]);

        $unpaidInvoice = Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-PO-UNPAID-1',
            'status' => InvoiceStatus::Unpaid,
        ]);
        $paidInvoice = Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-PO-PAID-1',
            'status' => InvoiceStatus::Paid,
        ]);

        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $unpaidInvoice->id,
            'amount' => 5000,
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $paidInvoice->id,
            'amount' => 7000,
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => null,
            'amount' => 3000,
        ]);

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.invoicedTotal', 12000)
            ->assertJsonPath('data.attributes.invoicePaidTotal', 7000)
            ->assertJsonPath('data.attributes.balance', 13000);
    });
});
