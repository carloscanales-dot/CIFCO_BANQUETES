<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Primero eliminamos las vistas si ya existen
        DB::statement('DROP VIEW IF EXISTS v_station_tickets;');
        DB::statement('DROP VIEW IF EXISTS v_tickets;');

        //  Crear vista v_station_tickets
        DB::statement("
                CREATE VIEW v_station_tickets AS
                SELECT
                    pr.id AS product_id,
                    st.id AS station_id,
                    u.id AS user_id,
                    st.station_name,
                    pr.product_name,
                    pr.unit_price,
                    pr.cost,
                    tc.uuid,
                    tc.created_at
                FROM
                    transactions tr
                JOIN stations st ON
                    (st.id = tr.station_id)
                JOIN station_users su ON
                    (st.id = su.station_id)
                JOIN users u ON
                    (su.user_id = u.id)
                JOIN station_tickets stt ON
                    (stt.station_id = st.id)
                JOIN tickets tc ON
                    (tc.id = stt.ticket_id)
                JOIN products pr ON
                    (pr.id = tc.product_id) ");

        // Crear vista v_tickets
        DB::statement("
            CREATE VIEW v_tickets AS
            SELECT
                tc.id AS ticket_id,
                tc.product_id AS product_id,
                pr.product_name AS product_name,
                tc.uuid AS uuid,
                pr.unit_price AS unit_price,
                tc.status AS status,
                tc.created_at AS created_at
            FROM tickets tc
            JOIN products pr ON tc.product_id = pr.id
        ");
    }

    public function down(): void
    {
        // Si hacemos rollback, eliminamos las vistas
        DB::statement('DROP VIEW IF EXISTS v_station_tickets;');
        DB::statement('DROP VIEW IF EXISTS v_tickets;');
    }
};
