<?php

use App\Models\Bill;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createIssuedBill(Transaction $transaction, int $amount = 1_000_000): Bill
{
    return $transaction->bill()->create([
        'bill_number' => 'INV-20260608-0001',
        'amount' => $amount,
        'status' => 'issued',
        'issued_at' => now(),
    ]);
}

describe('guest user', function () {
    test('cannot record a payment when not authenticated', function () {
        $transaction = Transaction::factory()->create();
        createIssuedBill($transaction);

        $this->postJson("/api/v1/transactions/{$transaction->id}/bill/payments", [
            'amount' => 500000,
            'method' => 'cash',
            'reference_number' => 'OR-0001',
        ])->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Storage::fake('public');
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can record a partial payment for own bill via bank transfer', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $bill = createIssuedBill($transaction, 1_000_000);

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/bill/payments", [
            'amount' => 400000,
            'method' => 'bank_transfer',
            'reference_number' => 'BTR-12345',
            'notes' => 'First installment',
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'bill-payment')
            ->assertJsonPath('data.attributes.amount', 400000)
            ->assertJsonPath('data.attributes.method', 'bank_transfer')
            ->assertJsonPath('data.attributes.referenceNumber', 'BTR-12345')
            ->assertJsonPath('data.relationships.bill.data.id', $bill->id);

        expect($response->json('data.attributes.proofImageUrl'))->not->toBeNull();

        $this->assertDatabaseHas('bill_payments', [
            'bill_id' => $bill->id,
            'amount' => 400000,
            'method' => 'bank_transfer',
            'reference_number' => 'BTR-12345',
        ]);

        expect($bill->fresh()->status)->toBe('partially_paid');
    });

    test('marks the bill as paid once payments cover the full amount', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $bill = createIssuedBill($transaction, 500000);

        $this->postJson("/api/v1/transactions/{$transaction->id}/bill/payments", [
            'amount' => 500000,
            'method' => 'gcash',
            'reference_number' => 'GC-99999',
            'proof_image' => UploadedFile::fake()->image('proof.png'),
        ])->assertCreated();

        $bill->refresh();

        expect($bill->status)->toBe('paid');
        expect($bill->paid_at)->not->toBeNull();
    });

    test('rejects a payment that exceeds the remaining balance', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        createIssuedBill($transaction, 500000);

        $this->postJson("/api/v1/transactions/{$transaction->id}/bill/payments", [
            'amount' => 600000,
            'method' => 'cash',
            'reference_number' => 'OR-0002',
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/amount');
    });

    test('can record a payment without a proof of payment image', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $bill = createIssuedBill($transaction);

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/bill/payments", [
            'amount' => 100000,
            'method' => 'cash',
            'reference_number' => 'OR-0003',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attributes.amount', 100000)
            ->assertJsonPath('data.attributes.proofImageUrl', null);

        $this->assertDatabaseHas('bill_payments', [
            'bill_id' => $bill->id,
            'amount' => 100000,
            'method' => 'cash',
            'reference_number' => 'OR-0003',
        ]);
    });

    test('rejects an invalid payment method', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        createIssuedBill($transaction);

        $this->postJson("/api/v1/transactions/{$transaction->id}/bill/payments", [
            'amount' => 100000,
            'method' => 'paypal',
            'reference_number' => 'OR-0004',
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/method');
    });

    test('returns 422 when bill is not yet issued', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $transaction->bill()->create([
            'bill_number' => 'INV-20260608-0002',
            'amount' => 500000,
            'status' => 'draft',
        ]);

        $this->postJson("/api/v1/transactions/{$transaction->id}/bill/payments", [
            'amount' => 100000,
            'method' => 'cash',
            'reference_number' => 'OR-0005',
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertStatus(422);
    });

    test('returns 404 when recording a payment for another users transaction', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);
        createIssuedBill($transaction);

        $this->postJson("/api/v1/transactions/{$transaction->id}/bill/payments", [
            'amount' => 100000,
            'method' => 'cash',
            'reference_number' => 'OR-0006',
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertNotFound();
    });
});
