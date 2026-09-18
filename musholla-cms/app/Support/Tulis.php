<?php

namespace App\Support;

use App\Services\BersihkanTampilan;

/**
 * Perapi tulisan.
 *
 * Di hosting ini WAF menolak isi POST multipart yang memuat tag HTML
 * (mis. "<p>"), sedangkan unggah gambar memaksa multipart. Karena itu isi
 * tulisan disimpan sebagai TEKS dengan penanda sederhana, lalu dirapikan
 * menjadi HTML di sini — saat tampil, bukan saat disimpan.
 *
 * Konten warisan WordPress yang sudah berisi HTML tetap diteruskan apa adanya
 * (dibersihkan oleh BersihkanTampilan).
 */
class Tulis
{
    public static function keHtml(?string $teks): string
    {
        $teks = (string) $teks;

        if (trim($teks) === '') {
            return '';
        }

        // Konten lama yang sudah HTML (warisan WP) → teruskan, tetap dibersihkan.
        if (str_contains($teks, '<')) {
            return BersihkanTampilan::bersihkan($teks);
        }

        $keluar = [];
        $paragraf = [];
        $daftar = [];
        $kutipan = [];

        $bersihkanSemua = function () use (&$paragraf, &$daftar, &$kutipan, &$keluar) {
            if ($paragraf) {
                $keluar[] = '<p>' . implode('<br>', array_map([self::class, 'sebaris'], $paragraf)) . '</p>';
                $paragraf = [];
            }
            if ($daftar) {
                $keluar[] = '<ul>' . implode('', array_map(fn ($b) => '<li>' . self::sebaris($b) . '</li>', $daftar)) . '</ul>';
                $daftar = [];
            }
            if ($kutipan) {
                $keluar[] = '<blockquote>' . implode('<br>', array_map([self::class, 'sebaris'], $kutipan)) . '</blockquote>';
                $kutipan = [];
            }
        };

        foreach (preg_split('/\R/', $teks) as $baris) {
            $t = trim($baris);

            if ($t === '') {
                $bersihkanSemua();
                continue;
            }

            if (preg_match('/^###\s+(.+)$/', $t, $m)) {
                $bersihkanSemua();
                $keluar[] = '<h3>' . self::sebaris($m[1]) . '</h3>';
                continue;
            }

            if (preg_match('/^##\s+(.+)$/', $t, $m)) {
                $bersihkanSemua();
                $keluar[] = '<h2>' . self::sebaris($m[1]) . '</h2>';
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $t, $m)) {
                if ($paragraf || $kutipan) {
                    $bersihkanSemua();
                }
                $daftar[] = $m[1];
                continue;
            }

            if (preg_match('/^>\s?(.*)$/', $t, $m)) {
                if ($paragraf || $daftar) {
                    $bersihkanSemua();
                }
                $kutipan[] = $m[1];
                continue;
            }

            if ($daftar || $kutipan) {
                $bersihkanSemua();
            }
            $paragraf[] = $t;
        }

        $bersihkanSemua();

        // sapuWarisan: false — HTML ini buatan kita sendiri, penyapu kode
        // warisan justru memakan tag yang berdampingan.
        return BersihkanTampilan::bersihkan(implode('', $keluar), false);
    }

    /** Penanda sebaris: **tebal**, *miring*, [teks](tautan). */
    public static function sebaris(string $teks): string
    {
        $teks = e($teks);

        $teks = preg_replace('#\[([^\]]+)\]\((https?://[^\s\)]+)\)#', '<a href="$2" rel="noopener">$1</a>', $teks);
        $teks = preg_replace('/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $teks);
        $teks = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $teks);

        return $teks;
    }

    /** Ringkasan tanpa penanda, untuk kartu & meta deskripsi. */
    public static function ringkas(?string $teks, int $batas = 150): string
    {
        $teks = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', (string) $teks);
        $teks = preg_replace('/^#{2,4}\s+/m', '', $teks);
        $teks = preg_replace('/^[-*>]\s+/m', '', $teks);
        $teks = str_replace(['**', '*'], '', $teks);

        return BersihkanTampilan::ringkas($teks, $batas);
    }
}
