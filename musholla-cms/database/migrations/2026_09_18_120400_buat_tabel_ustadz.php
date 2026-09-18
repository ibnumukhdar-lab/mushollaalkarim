<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ustadz', function (Blueprint $table) {

            $table->id();
            $table->string('nama');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('bidang', 100)->nullable();
            $table->string('no_wa', 25)->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('ustadz');
    }
};
