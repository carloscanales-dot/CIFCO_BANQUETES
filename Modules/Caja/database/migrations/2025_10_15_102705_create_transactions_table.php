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
            $table->id('transaction_id');
            $table->foreignId('station_id')->constrained('stations')->onUpdate('cascade');
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade');
            $table->decimal('amount', 10, 2);
            $table->dateTime('transaction_date')->useCurrent();
            $table->foreignId('transaction_type_id')->constrained('transaction_type')->onUpdate('cascade');
            $table->foreignId('status_id')->constrained('status')->onUpdate('cascade');
            $table->foreignId('payment_method_id')->constrained('payment_method')->onUpdate('cascade');
            $table->foreignId('payment_terminal_opening_id')->constrained('payment_terminal_opening')->onUpdate('cascade');
            $table->boolean('is_refunded')->default(false);
            $table->unsignedInteger('employee_id')->default(0);
            $table->timestamps();

            $table->foreign('employee_id')->references('employee_id')->on('employee')->onUpdate('cascade');
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
