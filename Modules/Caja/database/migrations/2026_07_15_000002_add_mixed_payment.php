<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Desglose para ventas con pago mixto (efectivo + tarjeta).
        // Null en ventas de un solo método.
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount_cash', 10, 2)->nullable()->after('amount');
            $table->decimal('amount_card', 10, 2)->nullable()->after('amount_cash');
        });

        // Método de pago MIXTO (id 4).
        if (! DB::table('payment_method')->where('payment_method_id', 4)->exists()) {
            DB::table('payment_method')->insert([
                'payment_method_id' => 4,
                'payment_method'    => 'MIXTO',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('payment_method')->where('payment_method_id', 4)->delete();

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['amount_cash', 'amount_card']);
        });
    }
};
