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
 *
 * Sejak panel memakai editor kaya (TinyMCE), isi yang ditulis pengurus dikirim
 * sebagai base64 lalu disimpan sebagai HTML dengan penanda App\Support\HtmlAman
 * di depannya. Isi berpenanda itu diteruskan apa adanya — hanya disaring tag
 * aman — sehingga gaya huruf, daftar, tautan, dan tabel tetap utuh.
 */
class Tulis
{
    public static function keHtml(?string $teks): string
    {
        $teks = (string) $teks;

        if (trim($teks) === '') {
            return '';
        }

        // Isi dari editor kaya di panel → pakai apa adanya (hanya disaring tag
        // aman). Jangan disapu sebagai isi warisan, penyapu itu memakan tag
        // berdampingan dan membuang tautan berkutip.
        if (HtmlAman::berkodeKaya($teks)) {
            return HtmlAman::isi($teks);
        }

        // Isi yang sudah berisi HTML:
        if (str_contains($teks, '<')) {
            // a. Warisan WordPress (tag/atribut desain lama) → sapu penuh seperti dulu.
            if (HtmlAman::warisan($teks)) {
                return BersihkanTampilan::bersihkan($teks);
            }

            // b. HTML yang sudah bersih (tulisan baru, hasil impor rapi) → JANGAN disapu:
            //    penyapu memakan tag berdampingan (</p>\n<h2> → </p h2>) dan membuang
            //    tautan berkutip. Cukup buang shortcode/skrip lalu saring tag aman.
            return HtmlAman::bersihkan(BersihkanTampilan::bersihkan($teks, false));
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
        // Isi editor kaya: buang tagnya saja, tanpa penyapu warisan.
        if (HtmlAman::berkodeKaya($teks)) {
            $html = HtmlAman::isi($teks);
            $html = preg_replace('~<(br|hr)\s*/?>~i', ' ', $html) ?? $html;
            $html = preg_replace('~</(p|h[1-6]|li|blockquote|div|tr|td|figure)\s*>~i', ' ', $html) ?? $html;
            $teks = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $teks = preg_replace('~\s+~u', ' ', (string) $teks) ?? $teks;

            return \Illuminate\Support\Str::limit(trim((string) $teks), $batas);
        }

        $teks = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', (string) $teks);
        $teks = preg_replace('/^#{2,4}\s+/m', '', $teks);
        $teks = preg_replace('/^[-*>]\s+/m', '', $teks);
        $teks = str_replace(['**', '*'], '', $teks);

        return BersihkanTampilan::ringkas($teks, $batas);
    }
}
