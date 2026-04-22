<?php

use App\Models\Bill;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\patchJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot update a bill when not authenticated', function () {
        $transaction = Transaction::factory()->create();

        patchJson("/api/v1/transactions/{$transaction->id}/bill", [
            'status' => 'issued',
        ])->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can transition draft to issued and set issued_at timestamp', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        Bill::factory()->create([
            'transaction_id' => $transaction->id,
            'status' => 'draft',
        ]);

        patchJson("/api/v1/transactions/{$transaction->id}/bill", [
            'status' => 'issued',
        ])->assertOk()
            ->assertJsonPath('data.attributes.status', 'issued');

        $this->assertDatabaseHas('bills', [
            'transaction_id' => $transaction->id,
            'status' => 'issued',
        ]);

        $this->assertDatabaseMissing('bills', [
            'transaction_id' => $transaction->id,
            'issued_at' => null,
            'status' => 'issued',
        ]);
    });

    test('can transition issued to paid and set paid_at timestamp', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        Bill::factory()->create([
            'transaction_id' => $transaction->id,
            'status' => 'issued',
            'issued_at' => now()->subDay(),
        ]);

        patchJson("/api/v1/transactions/{$transaction->id}/bill", [
            'status' => 'paid',
        ])->assertOk()
            ->assertJsonPath('data.attributes.status', 'paid');

        $this->assertDatabaseHas('bills', [
            'transaction_id' => $transaction->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseMissing('bills', [
            'transaction_id' => $transaction->id,
            'paid_at' => null,
            'status' => 'paid',
        ]);
    });

    test('returns 422 for invalid transition from draft to paid', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        Bill::factory()->create([
            'transaction_id' => $transaction->id,
            'status' => 'draft',
        ]);

        patchJson("/api/v1/transactions/{$transaction->id}/bill", [
            'status' => 'paid',
        ])->assertStatus(422);
    });

    test('returns 422 when trying to update terminal state bill', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        Bill::factory()->create([
            'transaction_id' => $transaction->id,
            'status' => 'paid',
            'paid_at' => now()->subHour(),
        ]);

        patchJson("/api/v1/transactions/{$transaction->id}/bill", [
            'amount' => 1500000,
        ])->assertStatus(422);
    });

    test('returns 404 when updating another users bill', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);
        Bill::factory()->create([
            'transaction_id' => $transaction->id,
            'status' => 'draft',
        ]);

        patchJson("/api/v1/transactions/{$transaction->id}/bill", [
            'status' => 'issued',
        ])->assertNotFound();
    });
});
