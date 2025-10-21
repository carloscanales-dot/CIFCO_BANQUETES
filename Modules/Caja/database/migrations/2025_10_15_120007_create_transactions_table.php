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
            $table->id();
            $table->foreignId('station_id')->constrained('stations')->onUpdate('cascade');
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade');
            $table->decimal('amount', 10, 2);
            $table->dateTime('transaction_date')->useCurrent();
            $table->foreignId('transaction_type_id')->constrained(table: 'transaction_type', column: 'transaction_type_id')->onUpdate('cascade');
            $table->foreignId('status_id')->constrained(table: 'status', column: 'status_id')->onUpdate('cascade');
            $table->foreignId('payment_method_id')->constrained(table: 'payment_method', column: 'payment_method_id')->onUpdate('cascade');
            $table->foreignId('payment_terminal_opening_id')->constrained('payment_terminal_opening')->onUpdate('cascade');
            $table->boolean('is_refunded')->default(false);
            $table->foreignId('employee_id')->constrained(table: 'employee', column: 'employee_id')->onUpdate('cascade');
            $table->timestamps();
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
