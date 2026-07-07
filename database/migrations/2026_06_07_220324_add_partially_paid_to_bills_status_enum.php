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
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('alter table bills drop constraint if exists bills_status_check');
            DB::statement("alter table bills alter column status type varchar(255), alter column status set not null, alter column status set default 'draft'");
            DB::statement("alter table bills add constraint bills_status_check check (status in ('draft', 'issued', 'partially_paid', 'paid', 'cancelled'))");

            return;
        }

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

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('alter table bills drop constraint if exists bills_status_check');
            DB::statement("alter table bills alter column status type varchar(255), alter column status set not null, alter column status set default 'draft'");
            DB::statement("alter table bills add constraint bills_status_check check (status in ('draft', 'issued', 'paid', 'cancelled'))");

            return;
        }

        Schema::table('bills', function (Blueprint $table): void {
            $table->enum('status', ['draft', 'issued', 'paid', 'cancelled'])
                ->default('draft')
                ->change();
        });
    }
};
