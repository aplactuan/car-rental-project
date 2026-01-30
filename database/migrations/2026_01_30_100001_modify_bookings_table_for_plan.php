<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignUuid('transaction_id')->after('id')->constrained('transactions')->cascadeOnDelete();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('notes', 'note');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('note', 'notes');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transaction_id');
        });
    }
};
