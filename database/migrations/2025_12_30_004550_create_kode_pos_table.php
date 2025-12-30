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
        Schema::create('kode_pos', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pos', 10)->unique();
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->foreignId('rajaongkir_sub_districts_id')
                ->nullable()
                ->constrained('rajaongkir_sub_districts')
                ->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('rajaongkir_sub_districts_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kode_pos');
    }
};
