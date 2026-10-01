<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menautkan infaq ↔ kas supaya pencatatan uang masuk punya SATU penentu:
 * kolom `kas_id` pada infaq (bukan lagi tebak-tebakan jenis+jumlah+tanggal+keterangan).
 *
 * Dibuat nullable + index (tanpa foreign key) karena basis data ini dipakai
 * bersama sisa tabel WordPress dan infaq lama belum tentu punya baris kas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('infaq', function (Blueprint $table) {
            $table->unsignedBigInteger('kas_id')->nullable()->after('diverifikasi_oleh');
            $table->index('kas_id');
        });

        Schema::table('kas', function (Blueprint $table) {
            $table->unsignedBigInteger('infaq_id')->nullable()->after('dicatat_oleh');
            $table->index('infaq_id');
        });
    }

    public function down(): void
    {
        Schema::table('infaq', function (Blueprint $table) {
            $table->dropIndex(['kas_id']);
            $table->dropColumn('kas_id');
        });

        Schema::table('kas', function (Blueprint $table) {
            $table->dropIndex(['infaq_id']);
            $table->dropColumn('infaq_id');
        });
    }
};
