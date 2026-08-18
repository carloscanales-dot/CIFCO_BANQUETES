<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Atar cada cortesía a una feria. Nullable para no romper tickets legacy.
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('fair_id')->nullable()->after('product_id')
                ->constrained('fairs')->onUpdate('cascade')->nullOnDelete();
        });

        // Recrear v_tickets incluyendo fair_id y fair_name (LEFT JOIN por legacy).
        DB::statement('DROP VIEW IF EXISTS v_tickets;');
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
                tc.fair_id AS fair_id,
                f.fair_name AS fair_name,
                tc.created_at AS created_at
            FROM tickets tc
            JOIN products pr ON tc.product_id = pr.id
            JOIN status st ON tc.status_id = st.id
            LEFT JOIN fairs f ON tc.fair_id = f.id
        ");
    }

    public function down(): void
    {
        // Restaurar la vista sin fair_id.
        DB::statement('DROP VIEW IF EXISTS v_tickets;');
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

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fair_id');
        });
    }
};
