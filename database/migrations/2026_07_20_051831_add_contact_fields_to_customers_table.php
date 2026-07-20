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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('contact_person')->nullable()->after('parent_id');
            $table->string('contact_mobile_number')->nullable()->after('contact_person');
            $table->string('contact_email')->nullable()->after('contact_mobile_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['contact_person', 'contact_mobile_number', 'contact_email']);
        });
    }
};
