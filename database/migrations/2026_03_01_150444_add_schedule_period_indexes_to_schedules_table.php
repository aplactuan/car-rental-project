<?php

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
        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['scheduleable_type', 'start_time'], 'schedules_type_start_idx');
            $table->index(['scheduleable_type', 'end_time'], 'schedules_type_end_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_type_start_idx');
            $table->dropIndex('schedules_type_end_idx');
        });
    }
};
