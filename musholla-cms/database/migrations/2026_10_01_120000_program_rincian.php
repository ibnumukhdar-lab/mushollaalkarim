<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Program donasi (wakaf & infaq) boleh punya RINCIAN KEBUTUHAN.
 *
 *  - wakaf_program.jenis          : 'wakaf' (barang) atau 'infaq' (ajakan dana,
 *                                   mis. Infaq Operasional). Bawaan 'wakaf'.
 *  - wakaf_program.periode_label  : keterangan periode, mis. "Kebutuhan setiap bulan".
 *  - wakaf_program.kata_kunci_kas : kata kunci pencocokan catatan KAS untuk progres
 *                                   berjalan (mis. "operasional"); kosong = pakai kolom terkumpul.
 *  - tabel program_rincian        : baris rincian (nama kebutuhan, jumlah, satuan,
 *                                   harga satuan) → subtotal & total dihitung aplikasi.
 *
 * Migrasi ini HANYA MENAMBAH kolom/tabel; kolom dan nilai yang sudah ada tidak disentuh
 * (basis data dipakai bersama tabel WordPress lama).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wakaf_program', function (Blueprint $table) {
            if (! Schema::hasColumn('wakaf_program', 'jenis')) {
                $table->string('jenis', 20)->default('wakaf');
            }
            if (! Schema::hasColumn('wakaf_program', 'periode_label')) {
                $table->string('periode_label', 80)->nullable();
            }
            if (! Schema::hasColumn('wakaf_program', 'kata_kunci_kas')) {
                $table->string('kata_kunci_kas', 80)->nullable();
            }
        });

        if (! Schema::hasTable('program_rincian')) {
            Schema::create('program_rincian', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wakaf_program_id')->constrained('wakaf_program')->cascadeOnDelete();
                $table->string('nama', 200);
                $table->decimal('jumlah', 10, 2)->default(1);
                $table->string('satuan', 30)->nullable();
                $table->decimal('harga_satuan', 14, 2)->default(0);
                $table->string('catatan', 255)->nullable();
                $table->integer('urutan')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('program_rincian');

        Schema::table('wakaf_program', function (Blueprint $table) {
            foreach (['jenis', 'periode_label', 'kata_kunci_kas'] as $kolom) {
                if (Schema::hasColumn('wakaf_program', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
