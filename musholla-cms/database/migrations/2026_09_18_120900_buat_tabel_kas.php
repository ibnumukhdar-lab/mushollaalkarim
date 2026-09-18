<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas', function (Blueprint $table) {

            $table->id();
            $table->date('tanggal');
            $table->enum('jenis', ['masuk', 'keluar'])->default('masuk');
            $table->string('kategori', 80)->nullable();
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->string('bukti_path')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tanggal', 'jenis']);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas');
    }
};
