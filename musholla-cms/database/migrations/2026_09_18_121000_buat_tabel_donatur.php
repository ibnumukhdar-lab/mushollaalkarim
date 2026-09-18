<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donatur', function (Blueprint $table) {

            $table->id();
            $table->string('nama');
            $table->string('no_wa', 25);
            $table->string('kategori', 80)->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index('kategori');
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('donatur');
    }
};
