<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sarprases', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->enum('name', ['terpasang', 'tiba', 'pengiriman', 'transit'])->unique();
            $table->timestamps();
        });

        Schema::create('koperasis', function (Blueprint $table) {
            $table->id();
            $table->string('ai_id')->nullable()->unique();
            $table->string('name');
            $table->foreignId('village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 12, 8)->nullable();
            $table->decimal('delivery_percentage', 5, 4)->nullable();
            $table->decimal('installed_percentage', 5, 4)->nullable();
            $table->decimal('core_percentage', 5, 4)->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index(['province_id', 'city_id', 'district_id', 'village_id']);
        });

        Schema::create('koperasi_sarpras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('koperasi_id')->constrained('koperasis')->cascadeOnDelete();
            $table->foreignId('sarpras_id')->constrained('sarprases')->cascadeOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->timestamps();

            $table->unique(['koperasi_id', 'sarpras_id']);
            $table->index(['status_id', 'sarpras_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('koperasi_sarpras');
        Schema::dropIfExists('koperasis');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('sarprases');
    }
};
