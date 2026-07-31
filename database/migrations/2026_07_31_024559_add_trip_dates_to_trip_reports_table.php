<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_reports', function (Blueprint $table) {
            $table->date('trip_start')->nullable()->after('report_date');
            $table->date('trip_end')->nullable()->after('trip_start');
        });

        DB::table('trip_reports')
            ->whereNull('trip_start')
            ->update([
                'trip_start' => DB::raw('DATE(created_at)'),
                'trip_end' => DB::raw('DATE(created_at)'),
            ]);

        Schema::table('trip_reports', function (Blueprint $table) {
            $table->date('trip_start')->nullable(false)->change();
            $table->date('trip_end')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('trip_reports', function (Blueprint $table) {
            $table->dropColumn(['trip_start', 'trip_end']);
        });
    }
};
