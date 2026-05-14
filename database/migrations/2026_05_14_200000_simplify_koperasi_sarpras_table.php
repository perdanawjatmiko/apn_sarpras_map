<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('koperasi_sarpras', function (Blueprint $table) {
            if (Schema::hasColumn('koperasi_sarpras', 'quantity')) {
                $table->dropColumn('quantity');
            }

            if (Schema::hasColumn('koperasi_sarpras', 'received_at')) {
                $table->dropColumn('received_at');
            }

            if (Schema::hasColumn('koperasi_sarpras', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('koperasi_sarpras', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1);
            $table->date('received_at')->nullable();
            $table->text('notes')->nullable();
        });
    }
};
