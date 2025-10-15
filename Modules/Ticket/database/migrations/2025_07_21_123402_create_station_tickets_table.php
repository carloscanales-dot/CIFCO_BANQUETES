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
        Schema::create('station_tickets', function (Blueprint $table) {
            $table->unsignedInteger('ticket_id');
            $table->unsignedInteger('station_id');
            // $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->foreign('ticket_id')->references('ticket_id')->on('tickets')->onUpdate('cascade');
            $table->foreign('station_id')->references('station_id')->on('station')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('station_tickets');
    }
};
