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

        // Crear vista v_station_tickets
        DB::statement("
            CREATE VIEW v_station_tickets AS
            SELECT 
                su.station_id AS station_id,
                su.user_id AS user_id,
                tc.product_id AS product_id,
                tt.station_name AS station_name,
                pr.product_name AS product_name,
                pr.unit_price AS unit_price,
                tc.uuid AS uuid,
                st.created_at AS created_at
            FROM station_tickets st
            JOIN station_users su ON st.station_user_id = su.station_user_id
            JOIN tickets tc ON st.ticket_id = tc.ticket_id
            JOIN stations tt ON su.station_id = tt.station_id
            JOIN products pr ON tc.product_id = pr.product_id
        ");

        // Crear vista v_tickets
        DB::statement("
            CREATE VIEW v_tickets AS
            SELECT 
                tc.ticket_id AS ticket_id,
                tc.product_id AS product_id,
                tc.user_id AS user_id,
                pr.product_name AS product_name,
                tc.uuid AS uuid,
                pr.unit_price AS unit_price,
                tc.status AS status,
                tc.created_at AS created_at
            FROM tickets tc
            JOIN products pr ON tc.product_id = pr.product_id
        ");
    }

    public function down(): void
    {
        // Si hacemos rollback, eliminamos las vistas
        DB::statement('DROP VIEW IF EXISTS v_station_tickets;');
        DB::statement('DROP VIEW IF EXISTS v_tickets;');
    }
};
