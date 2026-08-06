<?php

use App\Models\Program;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot delete a program when not authenticated', function () {
        $program = Program::factory()->create();

        deleteJson("/api/v1/programs/{$program->id}")
            ->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can delete a program', function () {
        $program = Program::factory()->create();

        deleteJson("/api/v1/programs/{$program->id}")
            ->assertNoContent();

        assertDatabaseMissing('programs', [
            'id' => $program->id,
        ]);
    });

    test('deleting a program keeps related purchase orders and nulls program_id', function () {
        $program = Program::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->forProgram($program)->create();

        deleteJson("/api/v1/programs/{$program->id}")
            ->assertNoContent();

        assertDatabaseMissing('programs', [
            'id' => $program->id,
        ]);

        assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'program_id' => null,
        ]);
    });
});
