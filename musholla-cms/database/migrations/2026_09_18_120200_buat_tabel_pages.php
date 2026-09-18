<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {

            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->longText('isi')->nullable();
            $table->string('ringkasan', 500)->nullable();
            $table->boolean('tampil_di_menu')->default(false);
            $table->integer('urutan_menu')->default(0);
            $table->string('meta_judul')->nullable();
            $table->string('meta_deskripsi', 500)->nullable();
            $table->timestamp('terbit_at')->nullable();
            $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
