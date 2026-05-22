<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaduans', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_id')->unique();
            $table->timestamp('ticket_date');
            $table->foreignUuid('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_name');
            $table->string('reporter_phone');
            $table->foreignId('koperasi_id')->nullable()->constrained('koperasis')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('pengaduan_categories')->nullOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained('pengaduan_categories')->nullOnDelete();
            $table->string('priority')->default('normal');
            $table->string('title');
            $table->text('detail');
            $table->foreignUuid('pic_helpdesk_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('baru');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sla_days')->nullable();
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->text('resolution')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_link')->nullable();
            $table->timestamp('last_update_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['reporter_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduans');
    }
};
