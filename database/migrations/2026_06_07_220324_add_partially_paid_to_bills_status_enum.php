<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            $table->enum('status', ['draft', 'issued', 'partially_paid', 'paid', 'cancelled'])
                ->default('draft')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('bills')->where('status', 'partially_paid')->update(['status' => 'issued']);

        Schema::table('bills', function (Blueprint $table): void {
            $table->enum('status', ['draft', 'issued', 'paid', 'cancelled'])
                ->default('draft')
                ->change();
        });
    }
};
