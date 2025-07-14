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
        Schema::create('rajaongkir_cities', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // ID dari API
            $table->string('name');
            $table->unsignedBigInteger('province_id'); // FK ke rajaongkir_provinces
            $table->timestamps();

            $table->foreign('province_id')->references('id')->on('rajaongkir_provinces')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rajaongkir_cities');
    }
};
