<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cost_id')->constrained('costs')->onDelete('cascade');
            $table->string('name');
            $table->string('code');
            $table->string('service');
            $table->string('description')->nullable();
            $table->unsignedInteger('cost');
            $table->string('etd')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_services');
    }
};
