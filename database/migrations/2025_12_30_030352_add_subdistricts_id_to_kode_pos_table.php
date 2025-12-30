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
        Schema::table('kode_pos', function (Blueprint $table) {
            $table->unsignedBigInteger('subdistricts_id')
                ->nullable()
                ->after('rajaongkir_sub_districts_id');

            $table->index('subdistricts_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kode_pos', function (Blueprint $table) {
            $table->dropColumn('subdistricts_id');
        });
    }
};
