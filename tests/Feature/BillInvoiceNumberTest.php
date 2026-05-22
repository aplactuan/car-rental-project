<?php

use App\Models\Bill;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('invoice number generation', function () {
    test('assigns the first invoice number for the current month', function () {
        $this->travelTo('2026-05-15');

        $bill = Bill::factory()->create();

        expect($bill->invoice_number)->toBe('INV-260500001');
    });

    test('increments invoice numbers within the same month', function () {
        $this->travelTo('2026-05-15');

        $first = Bill::factory()->create();
        $second = Bill::factory()->create();

        expect($first->invoice_number)->toBe('INV-260500001')
            ->and($second->invoice_number)->toBe('INV-260500002');
    });

    test('resets the sequence at the start of a new month', function () {
        $this->travelTo('2026-05-31');

        Bill::factory()->create();

        $this->travelTo('2026-06-01');

        $juneBill = Bill::factory()->create();

        expect($juneBill->invoice_number)->toBe('INV-260600001');
    });

    test('increments invoice numbers when creating bills through the api', function () {
        $this->travelTo('2026-05-15');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $firstTransaction = Transaction::factory()->create(['user_id' => $user->id]);
        $secondTransaction = Transaction::factory()->create(['user_id' => $user->id]);

        postJson("/api/v1/transactions/{$firstTransaction->id}/bill", [
            'amount' => 500000,
        ])->assertCreated()
            ->assertJsonPath('data.attributes.invoiceNumber', 'INV-260500001');

        postJson("/api/v1/transactions/{$secondTransaction->id}/bill", [
            'amount' => 600000,
        ])->assertCreated()
            ->assertJsonPath('data.attributes.invoiceNumber', 'INV-260500002');
    });
});
