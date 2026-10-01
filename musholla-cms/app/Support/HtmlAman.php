<?php

namespace App\Support;

/**
 * Penyaring HTML untuk isi yang ditulis lewat editor kaya di panel.
 *
 * Berbeda dari BersihkanTampilan (yang menyapu sisa kode WordPress pada isi
 * warisan dan justru memakan tag berdampingan), kelas ini MENJAGA hasil editor:
 * hanya tag dan atribut yang diizinkan yang dilewatkan, sisanya dibuang.
 *
 * Isi yang sudah melewati editor diberi penanda di awal teks (self::PENANDA)
 * supaya saat ditampilkan tidak ikut disapu sebagai isi warisan.
 */
class HtmlAman
{
    /** Penanda isi yang ditulis lewat editor kaya. */
    public const PENANDA = '<!--kaya-->';

    /** Tag yang diizinkan tampil. */
    private const TAG = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'del', 'ins', 'sub', 'sup',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote',
        'a', 'img', 'figure', 'figcaption', 'hr', 'table', 'thead', 'tbody', 'tfoot',
        'tr', 'th', 'td', 'caption', 'span', 'div', 'small', 'mark', 'code', 'pre',
    ];

    /** Atribut yang diizinkan. */
    private const ATRIBUT = [
        'href', 'target', 'rel', 'src', 'alt', 'title', 'width', 'height',
        'colspan', 'rowspan', 'style',
    ];

    /** Sifat gaya yang diizinkan di dalam atribut style (gaya huruf & tata letak dasar). */
    private const GAYA = [
        'font-family', 'font-size', 'font-weight', 'font-style', 'text-align',
        'text-decoration', 'color', 'background-color', 'line-height',
        'margin', 'padding', 'border', 'border-radius', 'width', 'height', 'max-width',
    ];

    /** Tag warisan WordPress yang akan dirusak editor kaya bila dibuka di sana. */
    private const TAG_WARISAN = 'section|article|figure|figcaption|button|iframe|font|center|fieldset|form|input|select|textarea|object|embed|canvas';

    /** Apakah teks ini berasal dari editor kaya? */
    public static function berkodeKaya(?string $teks): bool
    {
        return str_starts_with(ltrim((string) $teks), self::PENANDA);
    }

    /** Isi bersih (penanda dibuang) siap ditampilkan. */
    public static function isi(?string $teks): string
    {
        $teks = (string) $teks;
        if (self::berkodeKaya($teks)) {
            $teks = substr(ltrim($teks), strlen(self::PENANDA));
        }

        return self::bersihkan($teks);
    }

    /**
     * Isi lama (impor WordPress) yang sebaiknya TIDAK dibuka di editor kaya —
     * editor akan membuang tag/atribut yang tidak dikenalnya dan isinya rusak.
     */
    public static function warisan(?string $teks): bool
    {
        $teks = (string) $teks;
        if (trim($teks) === '' || self::berkodeKaya($teks)) {
            return false;
        }

        if (preg_match('~<(' . self::TAG_WARISAN . ')\b~i', $teks)) {
            return true;
        }

        if (preg_match('~\s(class|id|align|valign|bgcolor|border|data-[\w-]+)\s*=\s*["\']~i', $teks)) {
            return true;
        }

        // Gaya di luar daftar izin (mis. padding, ukuran piksel desain lama) → mode HTML mentah.
        if (preg_match_all('~\sstyle\s*=\s*"([^"]*)"~i', $teks, $m)) {
            foreach ($m[1] as $nilai) {
                foreach (explode(';', $nilai) as $satu) {
                    $satu = trim($satu);
                    if ($satu === '') {
                        continue;
                    }
                    $nama = strtolower(trim(explode(':', $satu)[0] ?? ''));
                    if (! in_array($nama, self::GAYA, true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /** Saring HTML: buang blok berbahaya, sisakan tag & atribut yang diizinkan. */
    public static function bersihkan(?string $html): string
    {
        $t = (string) $html;
        if (trim($t) === '') {
            return '';
        }

        // 1. blok berbahaya dibuang bersama isinya
        $t = preg_replace('~<(script|style|iframe|object|embed|form|textarea|svg|canvas|video|audio)\b[^>]*>.*?</\1\s*>~is', ' ', $t) ?? $t;
        $t = preg_replace('~<(script|style|iframe|object|embed|form|input|button|select|option|textarea|link|meta|base|svg|canvas)\b[^>]*/?>~i', ' ', $t) ?? $t;
        $t = preg_replace('~<!--.*?-->~s', ' ', $t) ?? $t;

        // 2. hanya tag yang diizinkan yang lolos
        $t = preg_replace_callback(
            '~<\s*(/?)\s*([a-zA-Z][a-zA-Z0-9]*)((?:\s+[^<>]*?)?)\s*(/?)\s*>~',
            function (array $m): string {
                $nama = strtolower($m[2]);
                if (! in_array($nama, self::TAG, true)) {
                    return ' ';
                }
                if ($m[1] === '/') {
                    return '</' . $nama . '>';
                }

                return '<' . $nama . self::saringAtribut($m[3]) . ($m[4] !== '' ? ' /' : '') . '>';
            },
            $t,
        ) ?? $t;

        // 3. rapikan sisa jarak
        $t = preg_replace('~[ \t]{2,}~', ' ', $t) ?? $t;
        $t = preg_replace('~(\r?\n){3,}~', "\n\n", $t) ?? $t;

        return trim($t);
    }

    /** Saring daftar atribut di dalam satu tag. */
    private static function saringAtribut(string $mentah): string
    {
        $keluar = [];

        if (preg_match_all('~([a-zA-Z][a-zA-Z0-9-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))~', $mentah, $cocok, PREG_SET_ORDER)) {
            foreach ($cocok as $a) {
                $nama = strtolower($a[1]);
                $nilai = $a[2] !== '' ? $a[2] : ($a[3] !== '' ? $a[3] : ($a[4] ?? ''));

                if (! in_array($nama, self::ATRIBUT, true)) {
                    continue;
                }

                if (in_array($nama, ['href', 'src'], true)) {
                    $sasaran = trim(html_entity_decode($nilai, ENT_QUOTES, 'UTF-8'));
                    if (preg_match('~^\s*(javascript|vbscript|data)\s*:~i', $sasaran)) {
                        continue;
                    }
                    if (! preg_match('~^(https?:|mailto:|tel:|/|#|\?|[A-Za-z0-9._-]+/)~', $sasaran)) {
                        continue;
                    }
                }

                if ($nama === 'style') {
                    $nilai = self::saringGaya($nilai);
                    if ($nilai === '') {
                        continue;
                    }
                }

                $keluar[] = $nama . '="' . e($nilai) . '"';
            }
        }

        return $keluar ? ' ' . implode(' ', $keluar) : '';
    }

    /** Saring isi atribut style: hanya sifat yang diizinkan, tanpa url/ekspresi. */
    private static function saringGaya(string $mentah): string
    {
        $sisa = [];

        foreach (explode(';', $mentah) as $satu) {
            if (! str_contains($satu, ':')) {
                continue;
            }
            [$nama, $nilai] = explode(':', $satu, 2);
            $nama = strtolower(trim($nama));
            $nilai = trim($nilai);

            if (! in_array($nama, self::GAYA, true)) {
                continue;
            }
            if (preg_match('~(expression|javascript|url\s*\(|@import)~i', $nilai)) {
                continue;
            }
            if (preg_match('~[<>"]~', $nilai)) {
                continue;
            }

            $sisa[] = $nama . ':' . $nilai;
        }

        return implode(';', $sisa);
    }
}
