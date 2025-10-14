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
            $table->unsignedInteger('ticket_id',$autoIncrement = true);
            $table->string('uuid',125);
            $table->enum('status',['D','C','A'])->default('D');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->foreign('product_id')->references('product_id')->on('products')->onUpdate('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onUpdate('cascade');
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
