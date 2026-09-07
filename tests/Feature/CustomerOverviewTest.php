<?php

use App\Enums\InvoiceStatus;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\PurchaseOrder;
use App\Models\Transaction;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot view the customer overview', function () {
        getJson('/api/v1/dashboard/customer-overview')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('returns an empty overview when no dashboard data exists', function () {
        getJson('/api/v1/dashboard/customer-overview')
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'customerOverview')
            ->assertJsonPath('data.id', 'overview')
            ->assertJsonPath('data.attributes.totalPrograms', 0)
            ->assertJsonPath('data.attributes.totalPurchaseOrderAmount', 0)
            ->assertJsonPath('data.attributes.totalBilled', 0)
            ->assertJsonPath('data.attributes.totalPaid', 0)
            ->assertJsonPath('data.attributes.balanceToCollect', 0)
            ->assertJsonCount(0, 'data.attributes.topCustomers');
    });

    test('returns the top five customers by their own purchase order value', function () {
        $parent = Customer::factory()->create(['name' => 'Parent Customer']);
        $child = Customer::factory()->forParent($parent)->create(['name' => 'Child Customer']);
        $third = Customer::factory()->create(['name' => 'Third Customer']);
        $fourth = Customer::factory()->create(['name' => 'Fourth Customer']);
        $fifth = Customer::factory()->create(['name' => 'Fifth Customer']);
        $sixth = Customer::factory()->create(['name' => 'Sixth Customer']);
        Customer::factory()->create(['name' => 'Customer Without Orders']);

        PurchaseOrder::factory()->forCustomer($parent)->create(['amount' => 6000]);
        PurchaseOrder::factory()->forCustomer($parent)->create(['amount' => 4000]);
        PurchaseOrder::factory()->forCustomer($child)->create(['amount' => 9000]);
        PurchaseOrder::factory()->forCustomer($third)->create(['amount' => 8000]);
        PurchaseOrder::factory()->forCustomer($fourth)->create(['amount' => 7000]);
        PurchaseOrder::factory()->forCustomer($fifth)->create(['amount' => 6000]);
        PurchaseOrder::factory()->forCustomer($sixth)->create(['amount' => 5000]);

        getJson('/api/v1/dashboard/customer-overview')
            ->assertSuccessful()
            ->assertJsonCount(5, 'data.attributes.topCustomers')
            ->assertJsonPath('data.attributes.topCustomers.0.id', $parent->id)
            ->assertJsonPath('data.attributes.topCustomers.0.name', $parent->name)
            ->assertJsonPath('data.attributes.topCustomers.0.type', $parent->type)
            ->assertJsonPath('data.attributes.topCustomers.0.purchaseOrderCount', 2)
            ->assertJsonPath('data.attributes.topCustomers.0.purchaseOrderTotal', 10000)
            ->assertJsonPath('data.attributes.topCustomers.1.id', $child->id)
            ->assertJsonPath('data.attributes.topCustomers.1.purchaseOrderTotal', 9000)
            ->assertJsonPath('data.attributes.topCustomers.2.id', $third->id)
            ->assertJsonPath('data.attributes.topCustomers.3.id', $fourth->id)
            ->assertJsonPath('data.attributes.topCustomers.4.id', $fifth->id)
            ->assertJsonMissing(['id' => $sixth->id]);
    });

    test('orders equal purchase order values by customer name', function () {
        $bravo = Customer::factory()->create(['name' => 'Bravo Customer']);
        $alpha = Customer::factory()->create(['name' => 'Alpha Customer']);

        PurchaseOrder::factory()->forCustomer($bravo)->create(['amount' => 5000]);
        PurchaseOrder::factory()->forCustomer($alpha)->create(['amount' => 5000]);

        getJson('/api/v1/dashboard/customer-overview')
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.topCustomers.0.id', $alpha->id)
            ->assertJsonPath('data.attributes.topCustomers.1.id', $bravo->id);
    });

    test('counts all programs including programs without purchase orders', function () {
        Program::factory()->count(3)->create();

        getJson('/api/v1/dashboard/customer-overview')
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.totalPrograms', 3);
    });

    test('sums only trip reports attached to unpaid purchase order invoices', function () {
        $customer = Customer::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create([
            'amount' => 700000,
        ]);

        $unpaidInvoice = Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-UNPAID-DASHBOARD',
            'status' => InvoiceStatus::Unpaid,
        ]);
        $paidInvoice = Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-PAID-DASHBOARD',
            'status' => InvoiceStatus::Paid,
        ]);

        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $unpaidInvoice->id,
            'amount' => 80000,
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $unpaidInvoice->id,
            'amount' => 48400,
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $paidInvoice->id,
            'amount' => 25000,
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => null,
            'amount' => 99999,
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
        ]);
        Bill::factory()->create([
            'transaction_id' => $transaction->id,
            'status' => 'issued',
            'amount' => 500000,
        ]);

        getJson('/api/v1/dashboard/customer-overview')
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.totalPurchaseOrderAmount', 700000)
            ->assertJsonPath('data.attributes.totalBilled', 153400)
            ->assertJsonPath('data.attributes.totalPaid', 25000)
            ->assertJsonPath('data.attributes.balanceToCollect', 128400);
    });

    test('loads the overview with four bounded aggregate queries', function () {
        DB::enableQueryLog();

        getJson('/api/v1/dashboard/customer-overview')->assertSuccessful();

        expect(DB::getQueryLog())->toHaveCount(4);
    });
});
