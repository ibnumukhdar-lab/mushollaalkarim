<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wakaf_program', function (Blueprint $table) {

            $table->id();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->decimal('target', 15, 2)->default(0);
            $table->decimal('terkumpul', 15, 2)->default(0);
            $table->string('gambar_path')->nullable();
            $table->integer('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('wakaf_program');
    }
};
