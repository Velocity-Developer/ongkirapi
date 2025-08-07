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
        Schema::create('rajaongkir_awb_manifests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rajaongkir_awb_id')->constrained()->onDelete('cascade');
            $table->string('manifest_code');
            $table->string('manifest_description');
            $table->date('manifest_date');
            $table->time('manifest_time');
            $table->string('city_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rajaongkir_awb_manifests');
    }
};
