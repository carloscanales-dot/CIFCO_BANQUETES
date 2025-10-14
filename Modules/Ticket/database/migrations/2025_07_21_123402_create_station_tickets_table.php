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
            $table->unsignedInteger('station_ticket_id', $autoIncrement = true);
            $table->unsignedInteger('ticket_id');
            $table->unsignedInteger('station_user_id');
            $table->timestamps();

            $table->foreign('ticket_id')->references('ticket_id')->on('tickets')->onUpdate('cascade');
            $table->foreign('station_user_id')->references('station_user_id')->on('station_users')->onUpdate('cascade');
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
