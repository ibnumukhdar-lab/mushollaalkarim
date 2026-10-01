<?php

namespace App\Support;

use App\Models\Page;

/**
 * Sumber TUNGGAL daftar menu publik.
 *
 * Dipakai oleh kepala situs, sidebar (tombol garis tiga), dan kaki situs —
 * supaya urutan, label, dan tautan selalu sama di semua tempat.
 */
class Menu
{
    /** Halaman bawaan plugin WordPress yang tidak boleh muncul di menu. */
    public const TERSEMBUNYI = [
        'home', 'home-page', 'beranda', 'info', 'info-situs', 'user', 'login', 'masuk',
        'register', 'daftar', 'anggota', 'members', 'logout', 'keluar', 'account', 'akun',
        'password-reset', 'sukses-daftar', 'privacy-policy', 'kebijakan-privasi',
        'berita', 'mari-berinfaq', 'laporan-kas', 'laporan-keuangan', 'pendaftaran-santri',
    ];

    /**
     * Menu utama situs (urutan tetap).
     *
     * @return list<array{judul: string, url: string, aktif: string, ikon: string}>
     */
    public static function utama(): array
    {
        $inti = [
            ['judul' => 'Beranda', 'url' => '/', 'aktif' => '/', 'ikon' => 'rumah'],
            ['judul' => 'Berita', 'url' => '/berita', 'aktif' => 'berita*', 'ikon' => 'kabar'],
            ['judul' => 'Mari Berinfaq', 'url' => '/mari-berinfaq', 'aktif' => 'mari-berinfaq', 'ikon' => 'infaq'],
            ['judul' => 'Laporan Keuangan', 'url' => '/laporan-kas', 'aktif' => 'laporan-kas', 'ikon' => 'kas'],
        ];

        // Halaman tambahan yang dibuat pengurus lewat panel ikut tampil di belakang menu inti.
        $tambahan = Page::query()
            ->whereNotNull('terbit_at')
            ->whereNotIn('slug', self::TERSEMBUNYI)
            ->orderBy('urutan_menu')
            ->orderBy('id')
            ->get(['judul', 'slug'])
            ->map(fn ($p) => [
                'judul' => $p->judul,
                'url' => '/' . $p->slug,
                'aktif' => $p->slug,
                'ikon' => 'halaman',
            ])
            ->all();

        // Tautan ke LUAR situs (bukan halaman aplikasi): selalu PALING AKHIR,
        // ditandai 'luar' => true supaya tata letak membukanya di tab baru.
        $luar = [
            [
                'judul' => 'Lokasi Musholla',
                'url' => 'https://maps.app.goo.gl/NFF6yzNhAWXwJMS68?g_st=ac',
                'aktif' => 'luar-lokasi-musholla',
                'ikon' => 'masjid',
                'luar' => true,
            ],
        ];

        return array_merge($inti, $tambahan, $luar);
    }
}
