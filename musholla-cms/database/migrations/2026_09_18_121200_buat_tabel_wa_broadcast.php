<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_broadcast', function (Blueprint $table) {

            $table->id();
            $table->foreignId('wa_template_id')->nullable()->constrained('wa_template')->nullOnDelete();
            $table->string('pesan_terkirim', 500)->nullable();
            $table->integer('jumlah_target')->default(0);
            $table->integer('terkirim')->default(0);
            $table->integer('gagal')->default(0);
            $table->timestamp('mulai_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->string('status', 30)->default('menunggu');
            $table->foreignId('oleh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_broadcast');
    }
};
