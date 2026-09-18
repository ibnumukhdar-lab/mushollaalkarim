<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Membersihkan isi halaman warisan WordPress agar layak tampil.
 *
 * Isi lama menyimpan blok kode (CSS/JS) dan shortcode milik WordPress —
 * misalnya [menu_tab_musholla], [simta_form_pendaftaran], atau [slider_berita].
 * Kalau dibiarkan, teks kode itu tampil berantakan di halaman.
 *
 * Dua aturan penting:
 *  1. Data asli di basis data TIDAK diubah — pembersihan hanya saat ditampilkan,
 *     sehingga bisa disetel ulang kapan saja tanpa impor ulang.
 *  2. Hanya membuang kode. Tidak ada tebakan "teks ini mirip kode", karena
 *     percobaan itu terbukti ikut memakan tulisan asli halaman.
 */
class BersihkanTampilan
{
    /** Shortcode WordPress yang tidak punya padanan di aplikasi baru. */
    private const SHORTCODE_DIBUANG = [
        'slider_berita', 'menu_tab_musholla', 'musholla_berita',
        'update_kas', 'kas_musholla', 'berita_musholla', 'laporan_kas',
        'simta_sapaan_ortu', 'simta_laporan_belajar', 'simta_form_pendaftaran',
        'simta_perkembangan_ustadz', 'simta_dashboard_ortu', 'simta_dashboard_ustadz',
    ];

    /** Awalan shortcode yang tidak punya padanan di aplikasi baru. */
    private const SHORTCODE_AWALAN = ['simta_', 'um_', 'musholla_', 'kas_', 'wa_'];

    public static function bersihkan(?string $html): string
    {
        if (! $html || trim($html) === '') {
            return '';
        }

        $t = $html;

        // 1. blok kode
        $t = preg_replace('~<script\b[^>]*>.*?</script>~is', ' ', $t) ?? $t;
        $t = preg_replace('~<style\b[^>]*>.*?</style>~is', ' ', $t) ?? $t;
        $t = preg_replace('~<noscript\b[^>]*>.*?</noscript>~is', ' ', $t) ?? $t;
        $t = preg_replace('~<!--.*?-->~s', ' ', $t) ?? $t;

        // 2. shortcode WordPress
        $t = preg_replace_callback(
            '~\[([a-z0-9_]+)([^\]]*)\](.*?)\[/\1\]~is',
            fn ($m) => self::shortcodeDibuang($m[1]) ? ' ' : $m[0],
            $t
        ) ?? $t;
        $t = preg_replace_callback(
            '~\[([a-z0-9_]+)([^\]]*)\]~i',
            fn ($m) => self::shortcodeDibuang($m[1]) ? ' ' : $m[0],
            $t
        ) ?? $t;

        // 3. sisa kode CSS yang terselip di dalam teks
        $t = self::sapuKode($t);

        // 4. keterangan widget lama yang tidak lagi berfungsi
        $t = preg_replace('~[^<>]{0,20}Memuat data [^<>]{0,40}~iu', ' ', $t) ?? $t;
        $t = preg_replace('~[^<>]{0,20}(Sedang memuat|Menghubungkan ke|Gagal memuat)[^<>]{0,40}~iu', ' ', $t) ?? $t;

        // 5. kejadian dalam tag yang menyisakan perilaku lama
        $t = preg_replace('~\s(?:onclick|onload|onerror|onchange|data-nosnippet)="[^"]*"~i', '', $t) ?? $t;

        // 6. wadah yang kini kosong
        for ($i = 0; $i < 5; $i++) {
            $t = preg_replace('~<(p|div|span|li|section|article)\b[^>]*>(?:\s|&nbsp;|<br\s*/?>)*</\1>~i', '', $t) ?? $t;
        }

        // 7. rapikan
        $t = preg_replace('~[ \t]{2,}~', ' ', $t) ?? $t;
        $t = preg_replace('~(\r?\n){3,}~', "\n\n", $t) ?? $t;

        return trim($t);
    }

    /** Ringkasan bersih untuk meta deskripsi / kartu berita. */
    public static function ringkas(?string $html, int $batas = 160): string
    {
        $teks = strip_tags(self::bersihkan($html));
        $teks = html_entity_decode($teks, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $teks = preg_replace('~\s+~u', ' ', $teks) ?? $teks;

        return Str::limit(trim($teks), $batas);
    }

    /**
     * Sapu potongan CSS. Teks di antara tag tidak pernah melewati batas tag,
     * jadi jangkauan pola tetap aman untuk kalimat di halaman.
     */
    private static function sapuKode(string $t): string
    {
        // 1. komentar CSS
        $t = preg_replace('~/\*.*?\*/~s', ' ', $t) ?? $t;

        // 2. aturan CSS berkurawal — diulang karena satu wadah bisa memuat banyak aturan
        for ($i = 0; $i < 20; $i++) {
            $baru = preg_replace('~[^<>{}]{0,240}\{[^{}<>]{0,1200}\}~s', ' ', $t) ?? $t;
            if ($baru === $t) {
                break;
            }
            $t = $baru;
        }

        // 3. deretan "sifat: nilai;" panjang (ciri khas CSS tanpa kurawal)
        $t = preg_replace('~(?:[a-z\-]{2,22}\s*:\s*[^;{}<>]{1,70};\s*){3,}~i', ' ', $t) ?? $t;

        return $t;
    }

    /** Apakah shortcode ini harus dibuang? */
    private static function shortcodeDibuang(string $nama): bool
    {
        $nama = strtolower($nama);
        if (in_array($nama, self::SHORTCODE_DIBUANG, true)) {
            return true;
        }
        foreach (self::SHORTCODE_AWALAN as $awalan) {
            if (str_starts_with($nama, $awalan)) {
                return true;
            }
        }

        return false;
    }
}
