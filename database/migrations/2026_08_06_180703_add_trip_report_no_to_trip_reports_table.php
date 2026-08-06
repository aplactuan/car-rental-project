<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_reports', function (Blueprint $table) {
            $table->string('trip_report_no')->nullable()->after('purchase_order_id');
        });

        $tripReports = DB::table('trip_reports')
            ->whereNull('trip_report_no')
            ->orderBy('id')
            ->get();

        foreach ($tripReports as $tripReport) {
            do {
                $tripReportNo = 'TR-'.Str::upper(Str::random(10));

                $exists = DB::table('trip_reports')
                    ->where('purchase_order_id', $tripReport->purchase_order_id)
                    ->where('trip_report_no', $tripReportNo)
                    ->exists();
            } while ($exists);

            DB::table('trip_reports')
                ->where('id', $tripReport->id)
                ->update(['trip_report_no' => $tripReportNo]);
        }

        Schema::table('trip_reports', function (Blueprint $table) {
            $table->string('trip_report_no')->nullable(false)->change();
        });

        Schema::table('trip_reports', function (Blueprint $table) {
            $table->unique(['purchase_order_id', 'trip_report_no']);
        });
    }

    public function down(): void
    {
        Schema::table('trip_reports', function (Blueprint $table) {
            $table->dropUnique(['purchase_order_id', 'trip_report_no']);
            $table->dropColumn('trip_report_no');
        });
    }
};
