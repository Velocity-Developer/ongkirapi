<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRajaongkirSubDistrictsTable extends Migration
{
    public function up(): void
    {
        Schema::create('rajaongkir_sub_districts', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // ID dari API
            $table->string('name');
            $table->string('zip_code', 10)->nullable();
            $table->unsignedBigInteger('district_id'); // FK ke rajaongkir_districts
            $table->timestamps();

            $table->foreign('district_id')
                ->references('id')
                ->on('rajaongkir_districts')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rajaongkir_sub_districts');
    }
}
