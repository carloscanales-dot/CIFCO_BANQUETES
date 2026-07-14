<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero eliminar duplicados, manteniendo solo el registro más antiguo
        DB::statement("
            DELETE st1 FROM station_tickets st1
            INNER JOIN station_tickets st2
            WHERE st1.id > st2.id
            AND st1.ticket_id = st2.ticket_id
            AND st1.station_id = st2.station_id
        ");

        Schema::table('station_tickets', function (Blueprint $table) {
            // Agregar índice único compuesto para evitar duplicados
            // Un ticket solo puede ser escaneado una vez en una estación
            $table->unique(['ticket_id', 'station_id'], 'unique_ticket_station');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('station_tickets', function (Blueprint $table) {
            $table->dropUnique('unique_ticket_station');
        });
    }
};
