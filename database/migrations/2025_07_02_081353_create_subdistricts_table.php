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
        Schema::create('subdistricts', function (Blueprint $table) {
            $table->id();
            $table->string('subdistrict_id')->unique();
            $table->string('city_id')->nullable();
            $table->string('province_id')->nullable();
            $table->string('province')->nullable();
            $table->string('type')->nullable();
            $table->string('city')->nullable();
            $table->string('subdistrict_name');
            $table->string('postal_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subdistricts');
    }
};
