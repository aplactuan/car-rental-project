<?php

use App\Models\PurchaseOrder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PurchaseOrder::query()->each(function (PurchaseOrder $purchaseOrder): void {
            $purchaseOrder->recomputeStatus();
        });
    }

    public function down(): void
    {
        //
    }
};
