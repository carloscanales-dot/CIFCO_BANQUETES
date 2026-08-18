<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jornada de venta: el evento opera pasada la medianoche, así que una venta
     * de la 01:00 pertenece al día anterior. Agrupar por DATE(transaction_date)
     * la manda al día siguiente y descuadra los cierres contra las actas.
     *
     * Columna generada (la calcula MySQL, no hay que mantenerla) con corte a las
     * 06:00. Indexada: los reportes filtran por fecha y whereDate() sobre el
     * datetime impedía usar índice.
     */
    public function up(): void
    {
        // Puede haberse aplicado a mano en producción antes del despliegue.
        if (Schema::hasColumn('transactions', 'jornada')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->date('jornada')
                ->storedAs('DATE(transaction_date - INTERVAL 6 HOUR)')
                ->after('transaction_date');

            $table->index('jornada', 'idx_transactions_jornada');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('transactions', 'jornada')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_jornada');
            $table->dropColumn('jornada');
        });
    }
};
