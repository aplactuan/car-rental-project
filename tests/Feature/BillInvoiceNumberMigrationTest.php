<?php

use App\Models\Bill;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('migration backfills invoice numbers for existing bills', function () {
    $this->travelTo('2026-05-15');

    Schema::table('bills', function ($table) {
        $table->dropUnique(['invoice_number']);
        $table->dropColumn('invoice_number');
    });

    $transaction = Transaction::factory()->create();

    DB::table('bills')->insert([
        'id' => (string) Str::uuid(),
        'transaction_id' => $transaction->id,
        'bill_number' => 'BILL-EXISTING-001',
        'amount' => 50_000,
        'status' => 'draft',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_05_22_143253_add_invoice_number_to_bills_table.php');
    $migration->up();

    expect(Bill::query()->where('bill_number', 'BILL-EXISTING-001')->value('invoice_number'))
        ->toBe('INV-260500001');
});
