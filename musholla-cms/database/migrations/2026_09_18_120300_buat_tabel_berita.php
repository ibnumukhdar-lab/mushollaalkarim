<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita', function (Blueprint $table) {

            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('ringkasan')->nullable();
            $table->longText('isi')->nullable();
            $table->string('kategori', 60)->nullable();
            $table->string('gambar_path')->nullable();
            $table->timestamp('terbit_at')->nullable();
            $table->foreignId('penulis_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita');
    }
};
