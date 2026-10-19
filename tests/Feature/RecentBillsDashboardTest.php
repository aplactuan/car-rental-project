<?php

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot view recent billing', function () {
        getJson('/api/v1/dashboard/billing/recent')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create());
    });

    test('returns an empty collection when there are no bills', function () {
        getJson('/api/v1/dashboard/billing/recent')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    test('returns only the ten most recently issued bills', function () {
        $bills = collect(range(1, 11))->map(
            fn (int $day): Bill => Bill::factory()->create([
                'bill_number' => sprintf('BILL-%02d', $day),
                'invoice_number' => sprintf('INV-BILL-%02d', $day),
                'amount' => 1000 * $day,
                'status' => 'issued',
                'issued_at' => sprintf('2026-08-%02d 09:00:00', $day),
                'due_at' => sprintf('2026-08-%02d 09:00:00', $day),
            ])
        );

        BillPayment::factory()->create([
            'bill_id' => $bills[10]->id,
            'amount' => 4000,
        ]);

        getJson('/api/v1/dashboard/billing/recent')
            ->assertSuccessful()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.id', $bills[10]->id)
            ->assertJsonPath('data.0.type', 'bill')
            ->assertJsonPath('data.0.attributes.billNumber', 'BILL-11')
            ->assertJsonPath('data.0.attributes.invoiceNumber', 'INV-BILL-11')
            ->assertJsonPath('data.0.attributes.amount', 11000)
            ->assertJsonPath('data.0.attributes.amountPaid', 4000)
            ->assertJsonPath('data.0.attributes.remainingBalance', 7000)
            ->assertJsonPath('data.0.attributes.status', 'issued')
            ->assertJsonPath('data.0.attributes.issuedAt', '2026-08-11T09:00:00+00:00')
            ->assertJsonPath('data.0.relationships.transaction.data.id', $bills[10]->transaction_id)
            ->assertJsonMissing(['id' => $bills[0]->id])
            ->assertJsonMissingPath('data.0.attributes.notes');
    });

    test('orders bills issued at the same time by bill number', function () {
        $bravo = Bill::factory()->create([
            'bill_number' => 'BILL-BRAVO',
            'status' => 'issued',
            'issued_at' => '2026-08-10 09:00:00',
        ]);
        $alpha = Bill::factory()->create([
            'bill_number' => 'BILL-ALPHA',
            'status' => 'paid',
            'issued_at' => '2026-08-10 09:00:00',
            'paid_at' => '2026-08-12 09:00:00',
        ]);

        getJson('/api/v1/dashboard/billing/recent')
            ->assertSuccessful()
            ->assertJsonPath('data.0.id', $alpha->id)
            ->assertJsonPath('data.1.id', $bravo->id)
            ->assertJsonPath('data.0.attributes.paidAt', '2026-08-12T09:00:00+00:00');
    });

    test('places bills without an issued date after issued bills', function () {
        $draft = Bill::factory()->create([
            'bill_number' => 'BILL-DRAFT',
            'status' => 'draft',
            'issued_at' => null,
        ]);
        $issued = Bill::factory()->create([
            'bill_number' => 'BILL-ISSUED',
            'status' => 'issued',
            'issued_at' => '2026-08-01 09:00:00',
        ]);

        getJson('/api/v1/dashboard/billing/recent')
            ->assertSuccessful()
            ->assertJsonPath('data.0.id', $issued->id)
            ->assertJsonPath('data.1.id', $draft->id);
    });

    test('loads recent billing with one bounded query', function () {
        Bill::factory()->create([
            'issued_at' => '2026-08-01 09:00:00',
        ]);

        DB::enableQueryLog();

        getJson('/api/v1/dashboard/billing/recent')->assertSuccessful();

        expect(DB::getQueryLog())->toHaveCount(1);
    });
});
