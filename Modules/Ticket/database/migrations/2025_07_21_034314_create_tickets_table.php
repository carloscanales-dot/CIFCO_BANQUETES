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
            $table->id();
            $table->string('uuid', 125)->unique(); // UUID del ticket
            $table->unsignedTinyInteger('status')->default(1); // Estado del ticket
            $table->foreignId('product_id')->constrained('products')->onUpdate('cascade'); // Producto canjeado
            $table->timestamp('redeem_date')->nullable(); // Fecha de canje (nullable si aún no se ha canjeado)
            $table->string('generated_for')->nullable();
            $table->timestamps();
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
