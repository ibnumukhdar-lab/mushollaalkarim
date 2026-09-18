<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::table('users', function (Blueprint $table) {

            // peran: admin | pengurus | ustadz | ortu | juri (satu peran utama per akun)
            $table->string('peran', 20)->default('ortu')->after('password');
            $table->string('nama_lengkap')->nullable()->after('peran');
            $table->string('no_wa', 25)->nullable()->after('nama_lengkap');
            $table->string('foto_path')->nullable()->after('no_wa');
            $table->boolean('aktif')->default(true)->after('foto_path');
            });

    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
