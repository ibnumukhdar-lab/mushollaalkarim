<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('santri', function (Blueprint $table) {

            $table->id();
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P'])->default('L');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('kelompok', 60)->nullable();
            $table->string('kelas_sekolah', 60)->nullable();
            $table->foreignId('ortu_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ustadz_id')->nullable()->constrained('ustadz')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->string('foto_path')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('santri');
    }
};
