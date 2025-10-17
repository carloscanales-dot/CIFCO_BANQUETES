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
        Schema::create('stations', function (Blueprint $table) {
            $table->id('station_id');
            $table->string('station_name', 100); // Nombre de la estación
            $table->boolean('status')->default(false); // Estado de la estación
            $table->foreignId('location_id')->constrained('location')->onUpdate('cascade'); // Ubicación asignada
            $table->foreignId('fair_id')->constrained('fairs')->onUpdate('cascade'); // Feria a la que pertenece
            //$table->foreignId('user_id')->constrained('users')->onUpdate('cascade'); // Usuario responsable (podría omitirlo si se ocupará la tabla payment_terminal)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.as
     */
    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
