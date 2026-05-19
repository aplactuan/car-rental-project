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
        Schema::create('cars', function (Blueprint $table) {
            // $table->id();
            $table->uuid('id')->primary();
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('type');
            $table->integer('door');
            $table->integer('seats');
            $table->string('color');
            $table->string('plate_number')->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
