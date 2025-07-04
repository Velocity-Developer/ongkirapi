<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_logs', function (Blueprint $table) {
            $table->id();

            $table->string('method')->default('POST');
            $table->string('endpoint');
            $table->enum('source', ['db', 'api']);

            $table->integer('status_code')->nullable();
            $table->boolean('success')->default(false);
            $table->integer('duration_ms')->nullable();

            $table->json('payload');
            $table->text('error_message')->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_logs');
    }
};
