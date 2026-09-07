<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('billed_at')->nullable()->after('status')->index();
            $table->timestamp('paid_at')->nullable()->after('billed_at')->index();
        });

        DB::table('invoices')
            ->whereNull('billed_at')
            ->update(['billed_at' => DB::raw('created_at')]);

        Schema::table('trip_reports', function (Blueprint $table) {
            $table->index('report_date');
        });
    }

    public function down(): void
    {
        Schema::table('trip_reports', function (Blueprint $table) {
            $table->dropIndex(['report_date']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['billed_at']);
            $table->dropIndex(['paid_at']);
            $table->dropColumn(['billed_at', 'paid_at']);
        });
    }
};
