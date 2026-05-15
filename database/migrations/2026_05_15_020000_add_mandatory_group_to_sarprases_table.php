<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sarprases', function (Blueprint $table) {
            $table->string('mandatory_group')->nullable()->after('is_mandatory')->index();
        });

        DB::table('sarprases')
            ->whereIn('slug', ['internet', 'starlink'])
            ->update(['mandatory_group' => 'koneksi_internet']);
    }

    public function down(): void
    {
        Schema::table('sarprases', function (Blueprint $table) {
            $table->dropColumn('mandatory_group');
        });
    }
};
