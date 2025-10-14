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
        Schema::create('station_users', function (Blueprint $table) {
            $table->unsignedInteger('station_user_id',$autoIncrement = true);
            $table->unsignedInteger('station_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->foreign('station_id')->references('station_id')->on('stations')->onUpdate('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('station_users');
    }
};
