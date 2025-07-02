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
        Schema::table('cities', function (Blueprint $table) {
            $table->string('province_id')->nullable()->change(); // ubah jadi nullable
            $table->dropUnique(['province_id']); // hapus index unik
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->string('province_id')->nullable(false)->change(); // balik ke not nullable
            $table->unique('province_id'); // tambahkan kembali unique
        });
    }
};
