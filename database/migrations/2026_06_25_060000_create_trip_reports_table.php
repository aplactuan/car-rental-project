<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->date('report_date');
            $table->string('po_number')->nullable();
            $table->string('time_in', 5)->nullable();
            $table->string('time_out', 5)->nullable();
            $table->unsignedInteger('rate')->nullable();
            $table->unsignedInteger('odometer_in')->nullable();
            $table->unsignedInteger('odometer_out')->nullable();
            $table->decimal('fuel_liters', 8, 2)->unsigned()->nullable();
            $table->unsignedInteger('fuel_amount')->nullable();
            $table->string('invoice_or_or_number')->nullable();
            $table->unsignedInteger('collection_amount')->nullable();
            $table->decimal('percentage', 5, 2)->unsigned()->nullable();
            $table->json('destinations')->nullable();
            $table->uuid('driver_id_snapshot')->nullable();
            $table->string('driver_name_snapshot')->nullable();
            $table->uuid('car_id_snapshot')->nullable();
            $table->string('car_make_snapshot')->nullable();
            $table->string('car_model_snapshot')->nullable();
            $table->string('car_plate_number_snapshot')->nullable();
            $table->uuid('customer_id_snapshot')->nullable();
            $table->string('customer_name_snapshot')->nullable();
            $table->string('transaction_name_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_reports');
    }
};
