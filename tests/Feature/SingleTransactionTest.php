<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot view a transaction when not authenticated', function () {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);

        getJson('/api/v1/transactions/'.$transaction->id)->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can view own transaction with nested bookings', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $transaction->bookings()->create([
            'car_id' => \App\Models\Car::factory()->create()->id,
            'driver_id' => \App\Models\Driver::factory()->create()->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(3),
            'note' => 'Test note',
        ]);

        $response = getJson('/api/v1/transactions/'.$transaction->id);

        $response->assertStatus(200);
        $json = $response->json();
        expect($json)->toHaveKey('data');
        $data = $json['data'];
        expect($data)->toHaveKeys(['type', 'id', 'attributes', 'relationships']);
        expect($data['id'])->toBe($transaction->id);
        expect($data['attributes']['userId'])->toBe($this->user->id);
        expect($data['relationships']['bookings']['data'])->toBeArray();
        expect($json)->toHaveKey('included');
        expect($json['included'][0]['attributes'])->toHaveKeys(['note', 'startDate', 'endDate']);
    });

    test('gets 404 when viewing another users transaction', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);

        getJson('/api/v1/transactions/'.$transaction->id)->assertStatus(404);
    });
});
