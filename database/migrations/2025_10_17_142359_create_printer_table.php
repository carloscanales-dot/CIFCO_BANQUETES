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
        Schema::create('printer', function (Blueprint $table) {
            $table->id('printer_id');
            $table->string('printer_name', 65);
            $table->boolean('status')->default(false);
            $table->foreignId('station_id')->constrained('stations')->onUpdate('cascade');
            $table->string('ip_adress')->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printer');
    }
};
