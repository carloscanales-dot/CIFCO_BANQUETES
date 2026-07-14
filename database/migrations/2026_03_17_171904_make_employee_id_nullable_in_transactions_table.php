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
        Schema::table('transactions', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['employee_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Modify the column to be nullable
            $table->unsignedBigInteger('employee_id')->nullable()->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Recreate the foreign key constraint
            $table->foreign('employee_id')->references('employee_id')->on('employee')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['employee_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Revert back to NOT NULL
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Recreate foreign key
            $table->foreign('employee_id')->references('employee_id')->on('employee')->onUpdate('cascade');
        });
    }
};
