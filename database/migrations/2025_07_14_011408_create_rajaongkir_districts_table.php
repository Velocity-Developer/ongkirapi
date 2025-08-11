<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRajaOngkirDistrictsTable extends Migration
{
    public function up(): void
    {
        Schema::create('rajaongkir_districts', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // ID dari API
            $table->string('name');
            $table->unsignedBigInteger('city_id'); // Foreign key ke rajaongkir_cities
            $table->timestamps();

            $table->foreign('city_id')
                ->references('id')
                ->on('rajaongkir_cities')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rajaongkir_districts');
    }
}
