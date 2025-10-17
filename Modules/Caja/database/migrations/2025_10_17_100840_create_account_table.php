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
        Schema::create('account', function (Blueprint $table) {
            $table->id('account_id');
            $table->foreignId('employee_id')->constrained('employee')->onUpdate('cascade');
            $table->decimal('account_balance', 10, 2)->default(0);
            $table->boolean('status')->default(false);
            $table->dateTime('opening_date')->useCurrent()->nullable(true);
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade');
            $table->dateTime('closing_date')->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account');
    }
};
