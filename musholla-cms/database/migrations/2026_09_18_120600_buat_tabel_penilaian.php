<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian', function (Blueprint $table) {

            $table->id();
            $table->foreignId('santri_id')->constrained('santri')->cascadeOnDelete();
            $table->foreignId('ustadz_id')->nullable()->constrained('ustadz')->nullOnDelete();
            $table->date('tanggal');
            $table->enum('jenis', ['hafalan', 'iqro', 'tilawah', 'adab'])->default('hafalan');
            $table->string('juz', 10)->nullable();
            $table->string('surah', 60)->nullable();
            $table->string('ayat', 30)->nullable();
            $table->string('iqro_jilid', 10)->nullable();
            $table->string('iqro_halaman', 10)->nullable();
            $table->string('nilai', 40)->nullable();
            $table->text('deskripsi')->nullable();
            $table->text('catatan_ustadz')->nullable();
            $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian');
    }
};
