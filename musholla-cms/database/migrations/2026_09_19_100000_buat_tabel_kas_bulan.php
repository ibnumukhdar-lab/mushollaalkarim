<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rekap kas per bulan — dasar "tutup kas".
 *
 * Satu baris = satu bulan (periode YYYY-MM). Baris baru lahir saat bulan ditutup
 * (angka dibekukan) dan saat bulan berikutnya dibuka dengan saldo awal = saldo akhir
 * bulan sebelumnya, sehingga saldo bersambung rapi dari bulan ke bulan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_bulan', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7)->unique();          // mis. 2026-09
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->decimal('masuk', 15, 2)->default(0);
            $table->decimal('keluar', 15, 2)->default(0);
            $table->decimal('saldo_akhir', 15, 2)->default(0);
            $table->boolean('ditutup')->default(false);
            $table->foreignId('ditutup_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditutup_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_bulan');
    }
};
