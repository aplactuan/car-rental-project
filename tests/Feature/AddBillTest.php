<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot create a bill when not authenticated', function () {
        $transaction = Transaction::factory()->create();

        postJson("/api/v1/transactions/{$transaction->id}/bill", [
            'amount' => 500000,
        ])->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can create a bill for own transaction', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        $response = postJson("/api/v1/transactions/{$transaction->id}/bill", [
            'amount' => 750000,
            'notes' => 'Initial billing',
            'due_at' => now()->addWeek()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'bill')
            ->assertJsonPath('data.attributes.amount', 750000)
            ->assertJsonPath('data.attributes.status', 'draft')
            ->assertJsonPath('data.relationships.transaction.data.id', $transaction->id);

        $this->assertDatabaseHas('bills', [
            'transaction_id' => $transaction->id,
            'amount' => 750000,
            'status' => 'draft',
        ]);
    });

    test('returns 409 when bill already exists for transaction', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $transaction->bill()->create([
            'bill_number' => 'INV-20260422-0001',
            'amount' => 600000,
            'status' => 'draft',
        ]);

        postJson("/api/v1/transactions/{$transaction->id}/bill", [
            'amount' => 700000,
        ])->assertStatus(409);
    });

    test('returns 422 when amount is invalid', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        postJson("/api/v1/transactions/{$transaction->id}/bill", [
            'amount' => 0,
        ])->assertStatus(422);
    });

    test('returns 404 when creating bill for another users transaction', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);

        postJson("/api/v1/transactions/{$transaction->id}/bill", [
            'amount' => 800000,
        ])->assertNotFound();
    });
});
