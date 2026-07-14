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
        // Primero, añadir la nueva columna status_id
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('uuid')->constrained('status')->onUpdate('cascade');
        });

        // Migrar datos existentes:
        // status = 1 (Disponible) -> status_id = 3 (PENDIENTE)
        // status = 0 (Canjeado) -> status_id = 1 (APLICADO)
        DB::table('tickets')->where('status', 1)->update(['status_id' => 3]); // Pendiente
        DB::table('tickets')->where('status', 0)->update(['status_id' => 1]); // Aplicado

        // Hacer status_id NOT NULL después de migrar los datos
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable(false)->change();
        });

        // Finalmente, eliminar la columna antigua status
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar la columna status
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1)->after('uuid');
        });

        // Migrar datos de vuelta:
        // status_id = 3 (PENDIENTE) -> status = 1
        // status_id = 1 (APLICADO) -> status = 0
        // status_id = 2 (ANULADO) -> status = 2
        DB::table('tickets')->where('status_id', 3)->update(['status' => 1]);
        DB::table('tickets')->where('status_id', 1)->update(['status' => 0]);
        DB::table('tickets')->where('status_id', 2)->update(['status' => 2]);

        // Eliminar status_id
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
