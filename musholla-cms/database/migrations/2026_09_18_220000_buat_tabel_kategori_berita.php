<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Kategori berita (pola sama dengan CMS masfahri.online):
 * tabel `kategori` + tabel penghubung `berita_kategori` (satu berita boleh banyak kategori).
 *
 * Data lama pada kolom `berita.kategori` (teks satu kategori) TIDAK diubah —
 * hanya disalin ke tabel penghubung supaya langsung terbaca sistem baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kategori')) {
            Schema::create('kategori', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 120);
                $table->string('slug', 140)->unique();
                $table->unsignedInteger('urut')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('berita_kategori')) {
            Schema::create('berita_kategori', function (Blueprint $table) {
                $table->unsignedBigInteger('berita_id');
                $table->unsignedBigInteger('kategori_id');
                $table->primary(['berita_id', 'kategori_id']);
                $table->index('kategori_id');
            });
        }

        // Salin kategori lama (teks) ke master + penghubung
        $lama = DB::table('berita')
            ->select('id', 'kategori')
            ->whereNotNull('kategori')
            ->where('kategori', '<>', '')
            ->get();

        foreach ($lama as $b) {
            $nama = trim((string) $b->kategori);
            if ($nama === '') {
                continue;
            }

            $kategori = DB::table('kategori')->where('nama', $nama)->first();
            if (! $kategori) {
                $slug = Str::slug($nama) ?: 'kategori';
                $n = 2;
                while (DB::table('kategori')->where('slug', $slug)->exists()) {
                    $slug = Str::slug($nama) . '-' . $n++;
                }
                $id = DB::table('kategori')->insertGetId([
                    'nama' => $nama,
                    'slug' => $slug,
                    'urut' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $id = $kategori->id;
            }

            $ada = DB::table('berita_kategori')->where('berita_id', $b->id)->where('kategori_id', $id)->exists();
            if (! $ada) {
                DB::table('berita_kategori')->insert(['berita_id' => $b->id, 'kategori_id' => $id]);
            }
        }

        // Kategori bawaan musholla bila belum ada
        foreach (['Kajian', 'Kegiatan', 'Pengumuman', 'Program'] as $i => $nama) {
            if (! DB::table('kategori')->where('nama', $nama)->exists()) {
                DB::table('kategori')->insert([
                    'nama' => $nama,
                    'slug' => Str::slug($nama),
                    'urut' => $i + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_kategori');
        Schema::dropIfExists('kategori');
    }
};
