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

    /**
     * @param  bool  $sapuWarisan  true = jalankan penyapu kode/skrip warisan
     *                             (untuk isi lama WordPress). Set false untuk
     *                             HTML yang dihasilkan aplikasi sendiri
     *                             (mis. hasil App\Support\Tulis) — penyapu itu
     *                             memakan tag yang berdampingan, mis. "</h2><p>".
     */
    public static function bersihkan(?string $html, bool $sapuWarisan = true): string
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

        if ($sapuWarisan) {
            // 3. sisa kode CSS yang terselip di dalam teks
            $t = self::sapuKode($t);

            // 3b. sisa kode JavaScript yang terselip sebagai teks biasa
            //     (isi lama kadang menyimpan potongan <script> tanpa tagnya)
            $t = self::sapuSkrip($t);

            // 4. keterangan widget lama yang tidak lagi berfungsi
            $t = preg_replace('~[^<>]{0,20}Memuat data [^<>]{0,40}~iu', ' ', $t) ?? $t;
            $t = preg_replace('~[^<>]{0,20}(Sedang memuat|Menghubungkan ke|Gagal memuat)[^<>]{0,40}~iu', ' ', $t) ?? $t;
        }

        // 5. kejadian dalam tag yang menyisakan perilaku lama
        $t = preg_replace('~\s(?:onclick|onload|onerror|onchange|data-nosnippet)="[^"]*"~i', '', $t) ?? $t;

        // 6. wadah yang kini kosong
        for ($i = 0; $i < 5; $i++) {
            $t = preg_replace('~<(p|div|span|li|section|article)\b[^>]*>(\s|&nbsp;|<br\s*/?>)*</\1>~i', '', $t) ?? $t;
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

    /**
     * Sapu potongan JavaScript yang ikut tersimpan sebagai teks.
     *
     * Isi warisan WordPress kadang menyimpan isi <script> tanpa tagnya, sehingga
     * kode tampil sebagai paragraf. Pola di bawah sengaja mensyaratkan tanda khas
     * kode (titik koma, =>, { }, atau penugasan DOM) supaya kalimat asli aman.
     */
    private static function sapuSkrip(string $t): string
    {
        // deklarasi: const/let/var ... = ...;
        $t = preg_replace('~\b(?:const|let|var)\s+[A-Za-z_$][\w$]*\s*=[^;<>{}]{1,300};?~u', ' ', $t) ?? $t;

        // fungsi: function nama(...) { ... }
        $t = preg_replace('~\b(?:async\s+)?function\s*[A-Za-z_$]*\s*\([^)]{0,120}\)\s*\{[^{}]{0,800}\}~u', ' ', $t) ?? $t;

        // panah: nama(...) => ...;
        $t = preg_replace('~[A-Za-z_$][\w$.]{0,40}\s*\([^()<>]{0,120}\)\s*=>\s*[^;<>{}]{0,200};?~u', ' ', $t) ?? $t;

        // penugasan properti DOM: document.x.y = ...;
        $t = preg_replace('~\b(?:document|window|this)\s*\.[\w.]{1,60}\s*=[^;<>{}]{0,200};~u', ' ', $t) ?? $t;

        // pemanggilan beruntun khas kode
        $t = preg_replace('~\.(?:forEach|then|catch|map|filter|push|querySelector(?:All)?|addEventListener|getElementById|innerHTML|innerText|style)\b[^;<>{}]{0,160};?~u', ' ', $t) ?? $t;

        // fetch/await
        $t = preg_replace('~\b(?:await\s+|const\s+\w+\s*=\s*)?fetch\s*\([^()<>]{0,200}\)[^;<>{}]{0,120};?~u', ' ', $t) ?? $t;

        // try / catch / finally blok
        $t = preg_replace('~\b(?:try|catch|finally)\s*(?:\([^)]{0,80}\))?\s*\{[^{}]{0,600}\}~u', ' ', $t) ?? $t;

        // JSON.stringify / .json() yang tersisa
        $t = preg_replace('~\bJSON\.\w+\([^()<>]{0,120}\)~u', ' ', $t) ?? $t;

        // URL internal WordPress / Apps Script yang bocor sebagai teks
        $t = preg_replace('~[\'"`](?:https?://|/wp-json/)[^\'"`<>]{0,200}[\'"`]~u', ' ', $t) ?? $t;

        // Pemeriksaan per kalimat: kalimat yang mengandung tanda khas kode dibuang,
        // kalimat bersih di sekitarnya tetap dipertahankan.
        $t = preg_replace_callback('~[^<>]{4,800}~u', function (array $m): string {
            $tanda = ['=>', '+=', '${', '`', 'console.', 'innerHTML', 'innerText', 'addEventListener',
                '.style', '.persen', '.info', 'function(', 'async ', '(){', '});', 'document.', 'window.',
                '||', '===', '!==', ' = ', ');', 'new Date', 'var(', 'json()',
                'document', 'window', 'console', 'querySelector', 'function', 'getElementById', 'innerHTML'];

            $bagian = preg_split('~(?<=[.;!?])\s+~u', $m[0]) ?: [$m[0]];
            $sisa = [];
            foreach ($bagian as $b) {
                $kotor = false;
                foreach ($tanda as $c) {
                    if (str_contains($b, $c)) {
                        $kotor = true;
                        break;
                    }
                }
                if (! $kotor) {
                    $sisa[] = $b;
                }
            }

            return implode(' ', $sisa);
        }, $t) ?? $t;

        // kurung kurawal sisa dari kode
        $t = str_replace(['{', '}'], ' ', $t);

        // sisa remah kode: warna heksa, tanda baca berdiri sendiri, potongan simbol
        $t = preg_replace('~#[0-9A-Fa-f]{3,8}~u', ' ', $t) ?? $t;
        $t = preg_replace('~(?<![a-zA-Z0-9])[^<>a-zA-Z0-9\s]{1,10}(?![a-zA-Z0-9])~u', ' ', $t) ?? $t;
        // remah pembanding dari kode (mis. ">> > 100 < 100")
        $t = preg_replace('~(?:[<>]{1,3}\s*){2,}~u', ' ', $t) ?? $t;
        $t = preg_replace('~[<>]=?\s*\d+(?![0-9])~u', ' ', $t) ?? $t;

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