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
        Schema::create('rajaongkir_awbs', function (Blueprint $table) {
            $table->id();
            $table->string('waybill_number', 100)->unique();
            $table->string('courier', 100)->nullable(); // jne, pos, tiki, dll
            $table->timestamp('waybill_date')->nullable();
            $table->decimal('weight', 10, 2)->nullable(); // in grams
            $table->string('shipper_name', 255)->nullable();
            $table->text('shipper_address')->nullable();
            $table->string('receiver_name', 255)->nullable();
            $table->text('receiver_address')->nullable();
            $table->string('status', 100)->nullable();
            $table->string('pod_receiver', 255)->nullable(); // proof of delivery receiver

            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rajaongkir_awbs');
    }
};
