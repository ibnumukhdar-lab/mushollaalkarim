<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul WhatsApp: antrean per penerima + aturan notifikasi otomatis.
 * Tabel wa_broadcast sudah ada (kampanye) — di sini ditambah kolom yang kurang.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. satu baris per penerima (inilah "antrean" yang bisa dipantau)
        Schema::create('wa_pesan', function (Blueprint $t) {
            $t->id();
            $t->foreignId('wa_broadcast_id')->nullable()->constrained('wa_broadcast')->nullOnDelete();
            $t->string('nama')->nullable();
            $t->string('nomor', 25);
            $t->text('pesan')->nullable();
            $t->string('status', 12)->default('menunggu');   // menunggu | terkirim | gagal
            $t->string('via', 12)->default('manual');        // manual | gateway | otomatis
            $t->timestamp('dikirim_at')->nullable();
            $t->string('galat')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });

        // 2. aturan notifikasi otomatis (mis. infaq diverifikasi → terima kasih)
        Schema::create('wa_aturan', function (Blueprint $t) {
            $t->id();
            $t->string('kunci')->unique();
            $t->string('nama');
            $t->boolean('aktif')->default(false);
            $t->string('penerima')->default('otomatis');
            $t->text('isi')->nullable();
            $t->timestamps();
        });

        // 3. kampanye: tambah kolom yang belum ada
        Schema::table('wa_broadcast', function (Blueprint $t) {
            if (! Schema::hasColumn('wa_broadcast', 'judul')) {
                $t->string('judul')->nullable()->after('id');
            }
            if (! Schema::hasColumn('wa_broadcast', 'isi')) {
                $t->text('isi')->nullable()->after('judul');
            }
            if (! Schema::hasColumn('wa_broadcast', 'grup')) {
                $t->string('grup')->nullable()->after('isi');
            }
            if (! Schema::hasColumn('wa_broadcast', 'mode')) {
                $t->string('mode', 12)->default('manual')->after('grup');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_pesan');
        Schema::dropIfExists('wa_aturan');
        Schema::table('wa_broadcast', function (Blueprint $t) {
            foreach (['judul', 'isi', 'grup', 'mode'] as $k) {
                if (Schema::hasColumn('wa_broadcast', $k)) {
                    $t->dropColumn($k);
                }
            }
        });
    }
};
