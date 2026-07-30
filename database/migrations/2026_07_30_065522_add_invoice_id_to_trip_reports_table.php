<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_reports', function (Blueprint $table) {
            $table->foreignUuid('invoice_id')->nullable()->after('purchase_order_id')->constrained('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trip_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });
    }
};
