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
        Schema::create('payment_terminal', function (Blueprint $table) {
            $table->id('payment_terminal_id'); // Id de terminal de pago
            $table->string('terminal_name', 65); // Nombre del terminal de pago
            $table->boolean('status')->default(false); // Estado del terminal de pago
            $table->foreignId('station_id')->constrained('stations')->onUpdate('cascade'); // Estación asociada
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade'); // Usuario asignado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_terminal');
    }
};
