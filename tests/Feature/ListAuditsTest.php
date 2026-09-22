<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use OwenIt\Auditing\Models\Audit;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['audit.console' => true]);

    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, [], 'sanctum');
});

test('guest users cannot list audits', function () {
    auth('sanctum')->forgetUser();

    getJson('/api/v1/audits')->assertUnauthorized();
});

test('trip reports purchase orders and invoices record create update and delete events', function () {
    $customer = Customer::factory()->create();

    $purchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create();
    $purchaseOrder->update(['description' => 'Updated purchase order']);
    $purchaseOrder->delete();

    $tripReportPurchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create();
    $tripReport = TripReport::factory()->create([
        'purchase_order_id' => $tripReportPurchaseOrder->id,
    ]);
    $tripReport->update(['driver' => 'Updated Driver']);
    $tripReport->delete();

    $invoicePurchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create();
    $invoice = Invoice::query()->create([
        'purchase_order_id' => $invoicePurchaseOrder->id,
        'invoice_number' => 'INV-AUDIT-001',
        'note' => 'Initial note',
    ]);
    $invoice->update(['note' => 'Updated note']);
    $invoice->delete();

    foreach ([$purchaseOrder, $tripReport, $invoice] as $auditable) {
        $audits = Audit::query()
            ->where('auditable_type', $auditable::class)
            ->where('auditable_id', $auditable->id)
            ->orderBy('id')
            ->get();

        expect($audits->pluck('event')->all())
            ->toBe(['created', 'updated', 'deleted'])
            ->and($audits->pluck('customer_id')->unique()->all())
            ->toBe([$customer->id])
            ->and($audits->pluck('user_id')->unique()->all())
            ->toBe([$this->user->id]);
    }
});

test('lists paginated audits filtered by customer model event actor and record', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create();
    $tripReport = TripReport::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
    ]);

    PurchaseOrder::factory()->forCustomer($otherCustomer)->create();

    $query = http_build_query([
        'customer_id' => $customer->id,
        'auditable_type' => 'trip-report',
        'auditable_id' => $tripReport->id,
        'event' => 'created',
        'user_id' => $this->user->id,
        'date_from' => now()->toDateString(),
        'date_to' => now()->toDateString(),
        'per_page' => 10,
    ]);

    getJson("/api/v1/audits?{$query}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'audit')
        ->assertJsonPath('data.0.attributes.event', 'created')
        ->assertJsonPath('data.0.attributes.auditableType', 'trip-report')
        ->assertJsonPath('data.0.attributes.auditableId', $tripReport->id)
        ->assertJsonPath('data.0.attributes.customerId', $customer->id)
        ->assertJsonPath('data.0.relationships.user.data.id', (string) $this->user->id)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes' => [
                        'event',
                        'auditableType',
                        'auditableId',
                        'customerId',
                        'oldValues',
                        'newValues',
                        'changedFields',
                        'url',
                        'ipAddress',
                        'userAgent',
                        'createdAt',
                    ],
                    'relationships' => [
                        'user' => ['data'],
                    ],
                ],
            ],
            'links',
            'meta',
        ]);
});

test('keeps the customer filter snapshot after an audited record is deleted', function () {
    $customer = Customer::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create();

    $purchaseOrder->delete();

    getJson("/api/v1/audits?customer_id={$customer->id}&event=deleted")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.auditableType', 'purchase-order')
        ->assertJsonPath('data.0.attributes.customerId', $customer->id);
});

test('validates audit filters', function (string $query, string $field) {
    getJson("/api/v1/audits?{$query}")
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', "/data/attributes/{$field}");
})->with([
    'per page below minimum' => ['per_page=0', 'per_page'],
    'unknown customer' => ['customer_id=00000000-0000-4000-8000-000000000000', 'customer_id'],
    'invalid model' => ['auditable_type=customer', 'auditable_type'],
    'invalid record id' => ['auditable_id=not-a-uuid', 'auditable_id'],
    'invalid event' => ['event=restored', 'event'],
    'unknown actor' => ['user_id=999999', 'user_id'],
    'invalid date range' => ['date_from=2026-09-23&date_to=2026-09-22', 'date_to'],
]);
