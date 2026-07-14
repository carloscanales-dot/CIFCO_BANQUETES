<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero eliminamos la vista existente
        DB::statement('DROP VIEW IF EXISTS v_tickets');

        // Creamos la vista actualizada con status_id y join a la tabla status
        DB::statement("
            CREATE VIEW v_tickets AS
            SELECT
                tc.id AS ticket_id,
                tc.product_id AS product_id,
                pr.product_name AS product_name,
                tc.uuid AS uuid,
                pr.unit_price AS unit_price,
                tc.status_id AS status_id,
                st.status AS status,
                tc.generated_for AS generated_for,
                tc.created_at AS created_at
            FROM tickets tc
            JOIN products pr ON tc.product_id = pr.id
            JOIN status st ON tc.status_id = st.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar la vista anterior
        DB::statement('DROP VIEW IF EXISTS v_tickets');

        DB::statement("
            CREATE VIEW v_tickets AS
            SELECT
                tc.id AS ticket_id,
                tc.product_id AS product_id,
                pr.product_name AS product_name,
                tc.uuid AS uuid,
                pr.unit_price AS unit_price,
                tc.status AS status,
                tc.generated_for AS generated_for,
                tc.created_at AS created_at
            FROM tickets tc
            JOIN products pr ON tc.product_id = pr.id
        ");
    }
};
