<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program', function (Blueprint $table) {

            $table->id();
            $table->string('nama');
            $table->string('hari', 20)->nullable();
            $table->string('waktu', 30)->nullable();
            $table->string('tempat')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('program');
    }
};
