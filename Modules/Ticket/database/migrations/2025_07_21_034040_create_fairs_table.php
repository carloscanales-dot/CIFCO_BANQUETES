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
        Schema::create('fairs', function (Blueprint $table) {
            $table->id('fair_id');
            $table->string('fair_name', 65);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('status')->default(false); // Más eficiente que integer si solo usas pocos estados
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fairs');
    }
};
