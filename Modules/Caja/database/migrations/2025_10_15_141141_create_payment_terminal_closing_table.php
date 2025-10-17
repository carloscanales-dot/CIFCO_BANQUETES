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
        Schema::create('payment_terminal_closing', function (Blueprint $table) {
            $table->id('payment_terminal_closing_id');
            $table->foreignId('payment_terminal_opening_id')->constrained('payment_terminal_opening')->onUpdate('cascade');
            $table->foreignId('payment_terminal_id')->constrained('payment_terminal')->onUpdate('cascade');
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade');
            $table->timestamp('closing_date');
            $table->decimal('closing_amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_terminal_closing');
    }
};
