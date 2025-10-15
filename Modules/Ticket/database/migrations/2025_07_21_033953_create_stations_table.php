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
            $table->unsignedInteger('station_id', $autoIncrement = true);
            $table->string('station_name', 100); // Nombre de la estación
            $table->boolean('status')->default('1'); // Estado de la estación
            $table->unsignedInteger('location_id'); // Ubicacion asignada a la estación
            $table->unsignedInteger('fair_id'); // A que feria perteneció la estación
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onUpdate('cascade');
            $table->foreign('location_id')->references('location_id')->on('location')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
