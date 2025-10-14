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
            $table->unsignedInteger('station_product_id',$autoIncrement = true);
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('station_id');
            $table->timestamps();

            $table->foreign('product_id')->references('product_id')->on('products')->onUpdate('cascade');
            $table->foreign('station_id')->references('station_id')->on('stations')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('station_product');
    }
};
