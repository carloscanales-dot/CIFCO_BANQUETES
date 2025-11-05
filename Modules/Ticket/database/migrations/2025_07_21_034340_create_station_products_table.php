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
        Schema::create('station_products', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->onUpdate('cascade');
            $table->foreignId('station_id')->constrained('stations')->onUpdate('cascade');
            $table->timestamps();

            $table->unique(['product_id', 'station_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('station_products');
    }
};
