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
            $table->unsignedInteger('fair_id',$autoIncrement = true);
            $table->string('fair_name',65);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status',['Programada','Abierta','Cerrada'])->default('Abierta');
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onUpdate('cascade');
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
