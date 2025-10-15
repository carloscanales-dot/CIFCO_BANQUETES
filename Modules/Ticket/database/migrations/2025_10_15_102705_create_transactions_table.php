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
        Schema::create('transactions', function (Blueprint $table) {
            $table->unsignedInteger('transaction_id', $autoIncrement = true);
            $table->unsignedInteger('station_id');
            $table->unsignedInteger('user_id');
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('transaction_type_id');
            $table->unsignedInteger('transaction_status_id');
            $table->unsignedInteger('payment_method_id');
            $table->timestamps();

            $table->foreign('station_id')->references('station_id')->on('stations')->onUpdate('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onUpdate('cascade');
            $table->foreign('transaction_type_id')->references('transaction_type_id')->on('transaction_type')->onUpdate('cascade');
            $table->foreign('transaction_status_id')->references('transaction_status_id')->on('transaction_status')->onUpdate('cascade');
            $table->foreign('payment_method_id')->references('payment_method_id')->on('payment_method')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
