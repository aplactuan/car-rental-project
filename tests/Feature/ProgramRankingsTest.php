<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot view program rankings', function () {
        getJson('/api/v1/dashboard/program-rankings')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create());
    });

    test('returns empty rankings when no programs have dashboard activity', function () {
        getJson('/api/v1/dashboard/program-rankings')
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'programRankings')
            ->assertJsonPath('data.id', 'rankings')
            ->assertJsonCount(0, 'data.attributes.topPrograms')
            ->assertJsonCount(0, 'data.attributes.topBilledPrograms')
            ->assertJsonCount(0, 'data.attributes.topPaidPrograms');
    });

    test('returns only the top ten programs by purchase order value', function () {
        $programs = collect(range(1, 11))->map(function (int $position): Program {
            $program = Program::factory()->create(['name' => "Program {$position}"]);
            PurchaseOrder::factory()
                ->forProgram($program)
                ->forCustomer($program->customer)
                ->create(['amount' => (12 - $position) * 1000]);

            return $program;
        });

        getJson('/api/v1/dashboard/program-rankings')
            ->assertSuccessful()
            ->assertJsonCount(10, 'data.attributes.topPrograms')
            ->assertJsonPath('data.attributes.topPrograms.0.id', $programs[0]->id)
            ->assertJsonPath('data.attributes.topPrograms.0.purchaseOrderTotal', 11000)
            ->assertJsonPath('data.attributes.topPrograms.9.id', $programs[9]->id)
            ->assertJsonMissing(['id' => $programs[10]->id]);
    });

    test('uses program name as the deterministic tie breaker', function () {
        $bravo = Program::factory()->create(['name' => 'Bravo Program']);
        $alpha = Program::factory()->create(['name' => 'Alpha Program']);

        PurchaseOrder::factory()->forProgram($bravo)->forCustomer($bravo->customer)->create(['amount' => 5000]);
        PurchaseOrder::factory()->forProgram($alpha)->forCustomer($alpha->customer)->create(['amount' => 5000]);

        getJson('/api/v1/dashboard/program-rankings')
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.topPrograms.0.id', $alpha->id)
            ->assertJsonPath('data.attributes.topPrograms.1.id', $bravo->id);
    });

    test('limits billed and paid rankings to five programs', function () {
        $programs = collect(range(1, 6))->map(function (int $position): Program {
            $program = Program::factory()->create();
            $purchaseOrder = PurchaseOrder::factory()
                ->forProgram($program)
                ->forCustomer($program->customer)
                ->create();
            $invoice = Invoice::query()->create([
                'purchase_order_id' => $purchaseOrder->id,
                'invoice_number' => "INV-RANK-{$position}",
                'status' => InvoiceStatus::Paid,
            ]);

            TripReport::factory()->for($purchaseOrder)->create([
                'invoice_id' => $invoice->id,
                'amount' => (7 - $position) * 1000,
            ]);

            return $program;
        });

        $response = getJson('/api/v1/dashboard/program-rankings')
            ->assertSuccessful()
            ->assertJsonCount(5, 'data.attributes.topBilledPrograms')
            ->assertJsonCount(5, 'data.attributes.topPaidPrograms');

        expect(collect($response->json('data.attributes.topBilledPrograms'))->pluck('id'))
            ->not->toContain($programs[5]->id)
            ->and(collect($response->json('data.attributes.topPaidPrograms'))->pluck('id'))
            ->not->toContain($programs[5]->id);
    });

    test('ranks billed and paid programs from attached trip report amounts', function () {
        $firstProgram = Program::factory()->create(['name' => 'First Program']);
        $secondProgram = Program::factory()->create(['name' => 'Second Program']);
        $firstPurchaseOrder = PurchaseOrder::factory()
            ->forProgram($firstProgram)
            ->forCustomer($firstProgram->customer)
            ->create();
        $secondPurchaseOrder = PurchaseOrder::factory()
            ->forProgram($secondProgram)
            ->forCustomer($secondProgram->customer)
            ->create();

        $firstPaidInvoice = Invoice::query()->create([
            'purchase_order_id' => $firstPurchaseOrder->id,
            'invoice_number' => 'INV-FIRST-PAID',
            'status' => InvoiceStatus::Paid,
        ]);
        $firstUnpaidInvoice = Invoice::query()->create([
            'purchase_order_id' => $firstPurchaseOrder->id,
            'invoice_number' => 'INV-FIRST-UNPAID',
            'status' => InvoiceStatus::Unpaid,
        ]);
        $secondPaidInvoice = Invoice::query()->create([
            'purchase_order_id' => $secondPurchaseOrder->id,
            'invoice_number' => 'INV-SECOND-PAID',
            'status' => InvoiceStatus::Paid,
        ]);

        TripReport::factory()->for($firstPurchaseOrder)->create([
            'invoice_id' => $firstPaidInvoice->id,
            'amount' => 2000,
        ]);
        TripReport::factory()->for($firstPurchaseOrder)->create([
            'invoice_id' => $firstPaidInvoice->id,
            'amount' => 3000,
        ]);
        TripReport::factory()->for($firstPurchaseOrder)->create([
            'invoice_id' => $firstUnpaidInvoice->id,
            'amount' => 3000,
        ]);
        TripReport::factory()->for($firstPurchaseOrder)->create([
            'invoice_id' => null,
            'amount' => 99999,
        ]);
        TripReport::factory()->for($secondPurchaseOrder)->create([
            'invoice_id' => $secondPaidInvoice->id,
            'amount' => 6000,
        ]);

        $unprogrammedPurchaseOrder = PurchaseOrder::factory()->create(['program_id' => null]);
        $unprogrammedInvoice = Invoice::query()->create([
            'purchase_order_id' => $unprogrammedPurchaseOrder->id,
            'invoice_number' => 'INV-UNPROGRAMMED',
            'status' => InvoiceStatus::Paid,
        ]);
        TripReport::factory()->for($unprogrammedPurchaseOrder)->create([
            'invoice_id' => $unprogrammedInvoice->id,
            'amount' => 100000,
        ]);

        getJson('/api/v1/dashboard/program-rankings')
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.topBilledPrograms.0.id', $firstProgram->id)
            ->assertJsonPath('data.attributes.topBilledPrograms.0.billedTotal', 8000)
            ->assertJsonPath('data.attributes.topBilledPrograms.1.id', $secondProgram->id)
            ->assertJsonPath('data.attributes.topBilledPrograms.1.billedTotal', 6000)
            ->assertJsonPath('data.attributes.topPaidPrograms.0.id', $secondProgram->id)
            ->assertJsonPath('data.attributes.topPaidPrograms.0.paidTotal', 6000)
            ->assertJsonPath('data.attributes.topPaidPrograms.1.id', $firstProgram->id)
            ->assertJsonPath('data.attributes.topPaidPrograms.1.paidTotal', 5000);
    });

    test('loads all rankings with three aggregate queries', function () {
        Program::factory()->count(3)->create();

        DB::enableQueryLog();

        getJson('/api/v1/dashboard/program-rankings')->assertSuccessful();

        expect(DB::getQueryLog())->toHaveCount(3);
    });
});
