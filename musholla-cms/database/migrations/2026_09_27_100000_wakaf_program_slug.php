<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Program wakaf kini punya ALAMAT SENDIRI (/wakaf/<slug>) supaya bisa dibagikan
 * dan menampilkan gambar unggulan saat dibagikan.
 *
 * Migrasi ini HANYA MENAMBAH kolom `slug` (varchar 120, unik, boleh kosong);
 * kolom dan nilai lain tidak disentuh. Baris program yang sudah ada diisi slug
 * dari namanya lewat query builder — jadi `updated_at` ikut tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('wakaf_program', 'slug')) {
            Schema::table('wakaf_program', function (Blueprint $table) {
                $table->string('slug', 120)->nullable()->unique();
            });
        }

        // Isi slug untuk baris yang sudah ada (mis. "Wakaf Rak Buku" -> wakaf-rak-buku).
        $terpakai = [];
        foreach (DB::table('wakaf_program')->select('id', 'nama')->orderBy('id')->get() as $baris) {
            $dasar = Str::slug((string) $baris->nama) ?: 'program-wakaf';
            $calon = $dasar;
            $n = 2;
            while (in_array($calon, $terpakai, true)) {
                $calon = $dasar . '-' . $n++;
            }
            $terpakai[] = $calon;

            DB::table('wakaf_program')->where('id', $baris->id)->update(['slug' => $calon]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('wakaf_program', 'slug')) {
            Schema::table('wakaf_program', function (Blueprint $table) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            });
        }
    }
};
