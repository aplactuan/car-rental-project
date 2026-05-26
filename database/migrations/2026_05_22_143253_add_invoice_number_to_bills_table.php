<?php

use App\Models\Bill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('bills', 'invoice_number')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->string('invoice_number')->nullable()->after('bill_number');
            });
        }

        Bill::query()
            ->where(function ($query): void {
                $query->whereNull('invoice_number')
                    ->orWhere('invoice_number', '');
            })
            ->orderBy('created_at')
            ->each(function (Bill $bill): void {
                $bill->forceFill([
                    'invoice_number' => Bill::generateNextInvoiceNumber(),
                ])->saveQuietly();
            });

        if (! Schema::hasIndex('bills', 'bills_invoice_number_unique')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->unique('invoice_number');
            });
        }

        Schema::table('bills', function (Blueprint $table) {
            $table->string('invoice_number')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('bills', 'invoice_number')) {
            return;
        }

        if (Schema::hasIndex('bills', 'bills_invoice_number_unique')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->dropUnique('bills_invoice_number_unique');
            });
        }

        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn('invoice_number');
        });
    }
};
