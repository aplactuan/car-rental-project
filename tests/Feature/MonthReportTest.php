<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-09-15 12:00:00'));
});

afterEach(function () {
    $this->travelBack();
});

describe('guest user', function () {
    test('cannot view the month report', function () {
        getJson('/api/v1/dashboard/month-report')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create());
    });

    test('returns twelve zero-filled calendar months', function () {
        getJson('/api/v1/dashboard/month-report')
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'monthReport')
            ->assertJsonPath('data.id', 'last-12-months')
            ->assertJsonCount(12, 'data.attributes.months')
            ->assertJsonPath('data.attributes.months.0.month', '2025-10')
            ->assertJsonPath('data.attributes.months.0.totalBilled', 0)
            ->assertJsonPath('data.attributes.months.0.totalCollectible', 0)
            ->assertJsonPath('data.attributes.months.0.totalPaid', 0)
            ->assertJsonPath('data.attributes.months.11.month', '2026-09');
    });

    test('groups billed collectible and paid amounts by their financial dates', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        $unpaidJanuaryInvoice = Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-UNPAID-JANUARY',
            'status' => InvoiceStatus::Unpaid,
            'billed_at' => '2026-01-10 10:00:00',
        ]);
        $paidFebruaryInvoice = Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-PAID-FEBRUARY',
            'status' => InvoiceStatus::Paid,
            'billed_at' => '2026-01-20 10:00:00',
            'paid_at' => '2026-02-15 10:00:00',
        ]);
        $historicalPaidInvoice = Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-HISTORICAL-PAID',
            'status' => InvoiceStatus::Paid,
            'billed_at' => '2025-12-05 10:00:00',
            'paid_at' => null,
        ]);
        $outsideRangeInvoice = Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-OUTSIDE-RANGE',
            'status' => InvoiceStatus::Unpaid,
            'billed_at' => '2025-09-30 23:59:59',
        ]);

        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $unpaidJanuaryInvoice->id,
            'amount' => 100,
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $paidFebruaryInvoice->id,
            'amount' => 60,
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $historicalPaidInvoice->id,
            'amount' => 40,
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $outsideRangeInvoice->id,
            'amount' => 999,
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => null,
            'amount' => 500,
        ]);

        $response = getJson('/api/v1/dashboard/month-report')
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.months.2.month', '2025-12')
            ->assertJsonPath('data.attributes.months.2.totalBilled', 40)
            ->assertJsonPath('data.attributes.months.2.totalCollectible', 0)
            ->assertJsonPath('data.attributes.months.2.totalPaid', 0)
            ->assertJsonPath('data.attributes.months.3.month', '2026-01')
            ->assertJsonPath('data.attributes.months.3.totalBilled', 160)
            ->assertJsonPath('data.attributes.months.3.totalCollectible', 100)
            ->assertJsonPath('data.attributes.months.3.totalPaid', 0)
            ->assertJsonPath('data.attributes.months.4.month', '2026-02')
            ->assertJsonPath('data.attributes.months.4.totalBilled', 0)
            ->assertJsonPath('data.attributes.months.4.totalCollectible', 0)
            ->assertJsonPath('data.attributes.months.4.totalPaid', 60);

        expect(collect($response->json('data.attributes.months'))->sum('totalPaid'))->toBe(60);
    });

    test('loads the month report with two grouped queries', function () {
        DB::enableQueryLog();

        getJson('/api/v1/dashboard/month-report')->assertSuccessful();

        expect(DB::getQueryLog())->toHaveCount(2);
    });
});
