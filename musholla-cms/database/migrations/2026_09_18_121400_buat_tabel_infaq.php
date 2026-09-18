<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infaq', function (Blueprint $table) {

            $table->id();
            $table->string('nama_donatur');
            $table->string('no_wa', 25)->nullable();
            $table->decimal('nominal', 15, 2)->default(0);
            $table->date('tanggal');
            $table->string('tujuan', 60)->nullable();
            $table->string('bukti_path')->nullable();
            $table->enum('status', ['menunggu', 'terverifikasi', 'ditolak'])->default('menunggu');
            $table->text('keterangan')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'tanggal']);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('infaq');
    }
};
