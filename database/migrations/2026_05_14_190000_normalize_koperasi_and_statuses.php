<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('statuses')) {
            Schema::create('statuses', function (Blueprint $table) {
                $table->id();
                $table->enum('name', ['terpasang', 'tiba', 'pengiriman', 'transit', 'tanpa_status'])->unique();
                $table->timestamps();
            });
        }

        $now = now();

        foreach (['terpasang', 'tiba', 'pengiriman', 'transit'] as $status) {
            DB::table('statuses')->updateOrInsert(
                ['name' => $status],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $shouldAddStatusIndex = false;

        if (! Schema::hasColumn('koperasi_sarpras', 'status_id')) {
            Schema::table('koperasi_sarpras', function (Blueprint $table) {
                $table->foreignId('status_id')->nullable()->after('sarpras_id')->constrained('statuses')->nullOnDelete();
            });

            $shouldAddStatusIndex = true;
        }

        $statuses = DB::table('statuses')->pluck('id', 'name');

        if (Schema::hasColumn('koperasi_sarpras', 'status')) {
            DB::table('koperasi_sarpras')
                ->whereRaw('LOWER(status) LIKE ?', ['%terpasang%'])
                ->update(['status_id' => $statuses['terpasang']]);
            DB::table('koperasi_sarpras')
                ->whereRaw('LOWER(status) LIKE ?', ['%tiba%'])
                ->update(['status_id' => $statuses['tiba']]);
            DB::table('koperasi_sarpras')
                ->whereRaw('LOWER(status) LIKE ?', ['%pengiriman%'])
                ->update(['status_id' => $statuses['pengiriman']]);
            DB::table('koperasi_sarpras')
                ->whereRaw('LOWER(status) LIKE ?', ['%transit%'])
                ->update(['status_id' => $statuses['transit']]);

            Schema::table('koperasi_sarpras', function (Blueprint $table) {
                $table->dropIndex(['status', 'sarpras_id']);
                $table->dropColumn('status');
            });

            $shouldAddStatusIndex = true;
        }

        if ($shouldAddStatusIndex) {
            Schema::table('koperasi_sarpras', function (Blueprint $table) {
                $table->index(['status_id', 'sarpras_id']);
            });
        }

        if (Schema::hasColumn('koperasis', 'provider')) {
            Schema::table('koperasis', function (Blueprint $table) {
                $table->dropColumn('provider');
            });
        }
    }

    public function down(): void
    {
        Schema::table('koperasis', function (Blueprint $table) {
            $table->string('provider')->nullable();
        });

        Schema::table('koperasi_sarpras', function (Blueprint $table) {
            $table->string('status')->nullable();
        });

        DB::table('koperasi_sarpras')
            ->join('statuses', 'statuses.id', '=', 'koperasi_sarpras.status_id')
            ->update(['status' => DB::raw('statuses.name')]);

        Schema::table('koperasi_sarpras', function (Blueprint $table) {
            $table->dropIndex(['status_id', 'sarpras_id']);
            $table->dropConstrainedForeignId('status_id');
            $table->index(['status', 'sarpras_id']);
        });

        Schema::dropIfExists('statuses');
    }
};
