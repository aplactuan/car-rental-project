<?php

use App\Models\BillPayment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createBillWithPayment(Transaction $transaction, int $billAmount, int $paymentAmount, string $billStatus): array
{
    $bill = $transaction->bill()->create([
        'bill_number' => 'INV-20260608-0020',
        'amount' => $billAmount,
        'status' => $billStatus,
        'issued_at' => now(),
        'paid_at' => $billStatus === 'paid' ? now() : null,
    ]);

    $payment = BillPayment::factory()->create([
        'bill_id' => $bill->id,
        'amount' => $paymentAmount,
    ]);

    $payment->addMedia(UploadedFile::fake()->image('proof.jpg'))->toMediaCollection(BillPayment::PROOF_MEDIA_COLLECTION);

    return [$bill, $payment];
}

describe('guest user', function () {
    test('cannot delete a payment when not authenticated', function () {
        $transaction = Transaction::factory()->create();
        [, $payment] = createBillWithPayment($transaction, 1_000_000, 1_000_000, 'paid');

        $this->deleteJson("/api/v1/transactions/{$transaction->id}/bill/payments/{$payment->id}")
            ->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Storage::fake('public');
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('deleting the only payment reverts a paid bill back to issued', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        [$bill, $payment] = createBillWithPayment($transaction, 1_000_000, 1_000_000, 'paid');

        $this->deleteJson("/api/v1/transactions/{$transaction->id}/bill/payments/{$payment->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('bill_payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('media', ['model_id' => $payment->id, 'model_type' => BillPayment::class]);

        $bill->refresh();
        expect($bill->status)->toBe('issued');
        expect($bill->paid_at)->toBeNull();
    });

    test('deleting one of several payments keeps the bill partially paid', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $bill = $transaction->bill()->create([
            'bill_number' => 'INV-20260608-0021',
            'amount' => 1_000_000,
            'status' => 'partially_paid',
            'issued_at' => now(),
        ]);

        $firstPayment = BillPayment::factory()->create(['bill_id' => $bill->id, 'amount' => 300000]);
        $secondPayment = BillPayment::factory()->create(['bill_id' => $bill->id, 'amount' => 200000]);

        $this->deleteJson("/api/v1/transactions/{$transaction->id}/bill/payments/{$secondPayment->id}")
            ->assertNoContent();

        $this->assertDatabaseHas('bill_payments', ['id' => $firstPayment->id]);
        $this->assertDatabaseMissing('bill_payments', ['id' => $secondPayment->id]);

        expect($bill->fresh()->status)->toBe('partially_paid');
    });

    test('returns 404 when deleting a payment for another users transaction', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);
        [, $payment] = createBillWithPayment($transaction, 1_000_000, 1_000_000, 'paid');

        $this->deleteJson("/api/v1/transactions/{$transaction->id}/bill/payments/{$payment->id}")
            ->assertNotFound();
    });
});
