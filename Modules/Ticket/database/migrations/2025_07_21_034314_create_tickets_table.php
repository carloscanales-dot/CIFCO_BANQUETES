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
        Schema::create('tickets', function (Blueprint $table) {
            $table->unsignedInteger('ticket_id', $autoIncrement = true);
            $table->string('uuid', 125)->unique(true); // UUID del ticket
            $table->integer('status')->default('1'); // Estado del ticket
            $table->unsignedInteger('product_id'); // Producto canjeado
            $table->timestamp('redeem_date'); // Fecha de canje
            $table->timestamps();

            $table->foreign('product_id')->references('product_id')->on('products')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
