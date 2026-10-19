<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot view recent invoices', function () {
        getJson('/api/v1/dashboard/invoices/recent')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create());
    });

    test('returns an empty collection when there are no invoices', function () {
        getJson('/api/v1/dashboard/invoices/recent')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    test('returns only the ten most recently billed invoices', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoices = collect(range(1, 11))->map(
            fn (int $day): Invoice => Invoice::query()->create([
                'purchase_order_id' => $purchaseOrder->id,
                'invoice_number' => sprintf('INV-RECENT-%02d', $day),
                'status' => InvoiceStatus::Unpaid,
                'billed_at' => sprintf('2026-08-%02d 08:00:00', $day),
            ])
        );

        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $invoices[10]->id,
            'amount' => 2500,
        ]);

        getJson('/api/v1/dashboard/invoices/recent')
            ->assertSuccessful()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.id', $invoices[10]->id)
            ->assertJsonPath('data.0.type', 'purchase-order-invoice')
            ->assertJsonPath('data.0.attributes.invoiceNumber', 'INV-RECENT-11')
            ->assertJsonPath('data.0.attributes.status', 'unpaid')
            ->assertJsonPath('data.0.attributes.amount', 2500)
            ->assertJsonPath('data.0.attributes.billedAt', '2026-08-11T08:00:00+00:00')
            ->assertJsonPath('data.0.attributes.paidAt', null)
            ->assertJsonPath('data.0.relationships.purchaseOrder.data.id', $purchaseOrder->id)
            ->assertJsonMissing(['id' => $invoices[0]->id])
            ->assertJsonMissingPath('data.0.attributes.note')
            ->assertJsonMissingPath('data.0.attributes.paymentReceiptUrl');
    });

    test('orders invoices billed at the same time by invoice number', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $bravo = Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-BRAVO',
            'status' => InvoiceStatus::Paid,
            'billed_at' => '2026-08-10 08:00:00',
            'paid_at' => '2026-08-12 08:00:00',
        ]);
        $alpha = Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-ALPHA',
            'status' => InvoiceStatus::Unpaid,
            'billed_at' => '2026-08-10 08:00:00',
        ]);

        getJson('/api/v1/dashboard/invoices/recent')
            ->assertSuccessful()
            ->assertJsonPath('data.0.id', $alpha->id)
            ->assertJsonPath('data.1.id', $bravo->id)
            ->assertJsonPath('data.1.attributes.paidAt', '2026-08-12T08:00:00+00:00');
    });

    test('places invoices without a billed date after billed invoices', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $unbilled = Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-UNBILLED',
            'status' => InvoiceStatus::Unpaid,
            'billed_at' => null,
        ]);
        $billed = Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-BILLED',
            'status' => InvoiceStatus::Unpaid,
            'billed_at' => '2026-08-01 08:00:00',
        ]);

        getJson('/api/v1/dashboard/invoices/recent')
            ->assertSuccessful()
            ->assertJsonPath('data.0.id', $billed->id)
            ->assertJsonPath('data.1.id', $unbilled->id);
    });

    test('loads recent invoices with one bounded query', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-QUERY',
            'status' => InvoiceStatus::Unpaid,
            'billed_at' => '2026-08-01 08:00:00',
        ]);

        DB::enableQueryLog();

        getJson('/api/v1/dashboard/invoices/recent')->assertSuccessful();

        expect(DB::getQueryLog())->toHaveCount(1);
    });
});
