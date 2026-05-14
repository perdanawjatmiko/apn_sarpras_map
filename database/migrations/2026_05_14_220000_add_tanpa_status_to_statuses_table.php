<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE statuses MODIFY name ENUM('terpasang', 'tiba', 'pengiriman', 'transit', 'tanpa_status') NOT NULL");
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE statuses DROP CONSTRAINT IF EXISTS statuses_name_check');
            DB::statement("ALTER TABLE statuses ADD CONSTRAINT statuses_name_check CHECK (name::text = ANY (ARRAY['terpasang'::character varying, 'tiba'::character varying, 'pengiriman'::character varying, 'transit'::character varying, 'tanpa_status'::character varying]::text[]))");
        }

        DB::table('statuses')->updateOrInsert(
            ['name' => 'tanpa_status'],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('statuses')->where('name', 'tanpa_status')->delete();

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE statuses MODIFY name ENUM('terpasang', 'tiba', 'pengiriman', 'transit') NOT NULL");
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE statuses DROP CONSTRAINT IF EXISTS statuses_name_check');
            DB::statement("ALTER TABLE statuses ADD CONSTRAINT statuses_name_check CHECK (name::text = ANY (ARRAY['terpasang'::character varying, 'tiba'::character varying, 'pengiriman'::character varying, 'transit'::character varying]::text[]))");
        }
    }
};
