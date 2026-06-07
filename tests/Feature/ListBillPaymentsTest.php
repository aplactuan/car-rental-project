<?php

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createBillForListing(Transaction $transaction): Bill
{
    return $transaction->bill()->create([
        'bill_number' => 'INV-20260608-0010',
        'amount' => 1_000_000,
        'status' => 'partially_paid',
        'issued_at' => now(),
    ]);
}

describe('guest user', function () {
    test('cannot list payments when not authenticated', function () {
        $transaction = Transaction::factory()->create();
        createBillForListing($transaction);

        $this->getJson("/api/v1/transactions/{$transaction->id}/bill/payments")
            ->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('lists payments recorded for own bill', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $bill = createBillForListing($transaction);

        BillPayment::factory()->count(2)->create(['bill_id' => $bill->id]);

        $response = $this->getJson("/api/v1/transactions/{$transaction->id}/bill/payments");

        $response->assertSuccessful();
        expect($response->json('data'))->toHaveCount(2);
    });

    test('returns 404 when listing payments for another users transaction', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);
        createBillForListing($transaction);

        $this->getJson("/api/v1/transactions/{$transaction->id}/bill/payments")
            ->assertNotFound();
    });
});
