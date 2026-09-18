<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Selaraskan tabel penilaian dengan bentuk data WordPress (kategori + detail + 3 nilai). */
    public function up(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->string('kategori', 80)->nullable()->after('jenis');
            $table->text('detail_materi')->nullable()->after('kategori');
            $table->unsignedTinyInteger('nilai_1')->nullable()->after('detail_materi');
            $table->unsignedTinyInteger('nilai_2')->nullable()->after('nilai_1');
            $table->unsignedTinyInteger('nilai_3')->nullable()->after('nilai_2');
            $table->string('ustadz_nama', 100)->nullable()->after('nilai_3');
        });
    }

    public function down(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'detail_materi', 'nilai_1', 'nilai_2', 'nilai_3', 'ustadz_nama']);
        });
    }
};
