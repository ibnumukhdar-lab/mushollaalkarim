<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Program wakaf: tambah jumlah barang, satuannya, dan harga per satuan.
 * Sebelumnya hanya ada satu angka "target", sehingga wakaf lebih dari satu
 * barang harus ditulis di dalam nama program (mis. "Rak Buku 3 Unit").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wakaf_program', function (Blueprint $table) {
            if (! Schema::hasColumn('wakaf_program', 'jumlah')) {
                $table->unsignedInteger('jumlah')->nullable()->after('keterangan');
            }
            if (! Schema::hasColumn('wakaf_program', 'satuan')) {
                $table->string('satuan', 30)->nullable()->after('jumlah');
            }
            if (! Schema::hasColumn('wakaf_program', 'harga_satuan')) {
                $table->unsignedBigInteger('harga_satuan')->nullable()->after('satuan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wakaf_program', function (Blueprint $table) {
            foreach (['jumlah', 'satuan', 'harga_satuan'] as $kolom) {
                if (Schema::hasColumn('wakaf_program', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
