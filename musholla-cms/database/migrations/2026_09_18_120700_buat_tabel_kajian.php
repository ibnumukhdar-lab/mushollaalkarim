<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kajian', function (Blueprint $table) {

            $table->id();
            $table->string('judul');
            $table->string('pemateri')->nullable();
            $table->string('tema')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('waktu_mulai', 10)->nullable();
            $table->string('waktu_selesai', 10)->nullable();
            $table->string('tempat')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('rutin_mingguan')->default(true);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('kajian');
    }
};
