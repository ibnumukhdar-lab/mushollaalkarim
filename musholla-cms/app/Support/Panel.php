<?php

namespace App\Support;

use App\Models\Berita;
use App\Models\Donatur;
use App\Models\Infaq;
use App\Models\Kajian;
use App\Models\Kas;
use App\Models\Kategori;
use App\Models\Page;
use App\Models\Program;
use App\Models\User;
use App\Models\WaBroadcast;
use App\Models\WaTemplate;
use App\Models\WakafProgram;

/**
 * Daftar modul panel pengelola (/kelola).
 *
 * Satu tempat untuk: kelompok menu, judul, ikon, kolom daftar, dan bidang formulir.
 * PanelController membaca definisi ini untuk daftar/tambah/ubah/hapus.
 *
 * Tipe bidang yang didukung: teks, teks-panjang, angka, uang, tanggal, tanggal-waktu,
 * waktu, pilihan, saklar, berkas, sandi.
 */
class Panel
{
    /** Kelompok menu di sidebar. */
    public const GRUP = [
        'konten' => ['judul' => 'Konten Situs', 'ikon' => 'kabar'],
        'keuangan' => ['judul' => 'Keuangan', 'ikon' => 'kas'],
        'jamaah' => ['judul' => 'Jamaah & Donatur', 'ikon' => 'akun'],
        'wa' => ['judul' => 'WhatsApp', 'ikon' => 'wa'],
        'sistem' => ['judul' => 'Sistem', 'ikon' => 'gear'],
    ];

    /** @return array<string, array<string, mixed>> */
    public static function modul(): array
    {
        return [
            /* ================= KONTEN ================= */
            'berita' => [
                'grup' => 'konten',
                'judul' => 'Berita',
                'judulSatu' => 'Berita',
                'ikon' => 'kabar',
                'model' => Berita::class,
                'keterangan' => 'Tulisan/kabar musholla. Kosongkan “Terbit pada” bila masih draf — draf tidak tampil di situs.',
                'cari' => ['judul', 'ringkasan', 'kategori'],
                'urut' => ['terbit_at' => 'desc', 'id' => 'desc'],
                'kolom' => [
                    ['nama' => 'gambar_path', 'label' => '', 'tipe' => 'gambar'],
                    ['nama' => 'judul', 'label' => 'Judul'],
                    ['nama' => 'kategori_utama', 'label' => 'Kategori', 'tipe' => 'lencana'],
                    ['nama' => 'terbit_at', 'label' => 'Terbit', 'tipe' => 'tanggal'],
                ],
                'field' => [
                    ['nama' => 'judul', 'label' => 'Judul tulisan', 'tipe' => 'teks', 'bagian' => 'Isi tulisan', 'wajib' => true, 'rules' => ['required', 'max:200'], 'lebar' => 'penuh'],
                    ['nama' => 'slug', 'label' => 'Slug (alamat)', 'tipe' => 'teks', 'bagian' => 'Isi tulisan', 'rules' => ['nullable', 'max:200'], 'bantuan' => 'Kosongkan agar dibuat otomatis dari judul.'],
                    ['nama' => 'isi', 'label' => 'Isi tulisan', 'tipe' => 'teks-panjang', 'bagian' => 'Isi tulisan', 'rules' => ['nullable'], 'baris' => 16, 'lebar' => 'penuh', 'editor' => true,
                        'bantuan' => 'Tombol menyisipkan penanda teks: ## sub-judul, **tebal**, *miring*, - daftar, > kutipan, [teks](tautan). Dirapikan otomatis saat tampil di situs.'],
                    ['nama' => 'terbit_at', 'label' => 'Terbit pada', 'tipe' => 'tanggal-waktu', 'bagian' => 'Publikasi', 'rules' => ['nullable', 'date'],
                        'bantuan' => 'Kosongkan = simpan sebagai draf (belum tampil di situs).'],
                    ['nama' => 'kategori_ids', 'label' => 'Kategori', 'tipe' => 'pilihan-banyak', 'bagian' => 'Publikasi', 'relasi' => 'kategoriBanyak',
                        'sumber' => Kategori::class, 'rules' => ['nullable', 'array'], 'bantuan' => 'Boleh pilih lebih dari satu — dipakai untuk chip & penyaringan di situs.'],
                    ['nama' => 'gambar_path', 'label' => 'Gambar sampul (1:1)', 'tipe' => 'berkas', 'bagian' => 'Publikasi', 'lebar' => 'penuh', 'mode' => 'potong',
                        'rules' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
                        'bantuan' => 'Tampil sebagai gambar sampul di kartu berita beranda & arsip.'],
                    ['nama' => 'ringkasan', 'label' => 'Ringkasan (opsional)', 'tipe' => 'teks-panjang', 'bagian' => 'Ringkasan (opsional)', 'rules' => ['nullable', 'max:500'], 'baris' => 3, 'lebar' => 'penuh',
                        'bantuan' => 'Tampil pada kartu berita di beranda. Bila kosong, diambil dari awal isi tulisan.'],
                ],
            ],

            'kategori' => [
                'grup' => 'konten',
                'judul' => 'Kategori',
                'judulSatu' => 'Kategori',
                'ikon' => 'halaman',
                'model' => Kategori::class,
                'keterangan' => 'Kategori dipakai untuk mengelompokkan berita (boleh banyak per tulisan).',
                'cari' => ['nama', 'slug'],
                'urut' => ['urut' => 'asc', 'nama' => 'asc'],
                'kolom' => [
                    ['nama' => 'nama', 'label' => 'Nama'],
                    ['nama' => 'slug', 'label' => 'Slug'],
                    ['nama' => 'urut', 'label' => 'Urutan', 'tipe' => 'angka'],
                ],
                'field' => [
                    ['nama' => 'nama', 'label' => 'Nama kategori', 'tipe' => 'teks', 'wajib' => true, 'rules' => ['required', 'max:120'], 'lebar' => 'penuh'],
                    ['nama' => 'slug', 'label' => 'Slug', 'tipe' => 'teks', 'rules' => ['nullable', 'max:140'], 'bantuan' => 'Kosongkan agar dibuat otomatis dari nama.'],
                    ['nama' => 'urut', 'label' => 'Urutan', 'tipe' => 'angka', 'rules' => ['nullable', 'integer']],
                ],
            ],

            'pages' => [
                'grup' => 'konten',
                'judul' => 'Halaman',
                'judulSatu' => 'Halaman',
                'ikon' => 'halaman',
                'model' => Page::class,
                'keterangan' => 'Halaman tetap situs (mis. profil, program, kebijakan).',
                'cari' => ['judul', 'slug'],
                'urut' => ['urutan_menu' => 'asc', 'id' => 'asc'],
                'kolom' => [
                    ['nama' => 'judul', 'label' => 'Judul'],
                    ['nama' => 'slug', 'label' => 'Slug'],
                    ['nama' => 'urutan_menu', 'label' => 'Urutan', 'tipe' => 'angka'],
                    ['nama' => 'terbit_at', 'label' => 'Terbit', 'tipe' => 'tanggal'],
                ],
                'field' => [
                    ['nama' => 'judul', 'label' => 'Judul halaman', 'tipe' => 'teks', 'wajib' => true, 'rules' => ['required', 'max:200'], 'lebar' => 'penuh'],
                    ['nama' => 'slug', 'label' => 'Slug (alamat)', 'tipe' => 'teks', 'rules' => ['nullable', 'max:200'], 'bantuan' => 'mis. profil-musholla → /profil-musholla'],
                    ['nama' => 'urutan_menu', 'label' => 'Urutan menu', 'tipe' => 'angka', 'rules' => ['nullable', 'integer', 'min:0']],
                    ['nama' => 'tampil_di_menu', 'label' => 'Tampilkan di menu situs', 'tipe' => 'saklar', 'rules' => ['nullable', 'boolean']],
                    ['nama' => 'ringkasan', 'label' => 'Ringkasan', 'tipe' => 'teks-panjang', 'rules' => ['nullable', 'max:500'], 'baris' => 2, 'lebar' => 'penuh'],
                    ['nama' => 'isi', 'label' => 'Isi halaman', 'tipe' => 'teks-panjang', 'rules' => ['nullable'], 'baris' => 16, 'lebar' => 'penuh'],
                    ['nama' => 'meta_judul', 'label' => 'Meta judul (SEO)', 'tipe' => 'teks', 'rules' => ['nullable', 'max:200']],
                    ['nama' => 'meta_deskripsi', 'label' => 'Meta deskripsi (SEO)', 'tipe' => 'teks', 'rules' => ['nullable', 'max:300']],
                    ['nama' => 'terbit_at', 'label' => 'Terbit mulai', 'tipe' => 'tanggal-waktu', 'rules' => ['nullable', 'date'], 'bantuan' => 'Kosongkan = belum terbit (tidak tampil di situs).'],
                ],
            ],

            'kajian' => [
                'grup' => 'konten',
                'judul' => 'Jadwal Kajian',
                'judulSatu' => 'Kajian',
                'ikon' => 'rumah',
                'model' => Kajian::class,
                'keterangan' => 'Jadwal kajian rutin maupun kegiatan khusus.',
                'cari' => ['judul', 'pemateri', 'tema', 'tempat'],
                'urut' => ['tanggal' => 'desc', 'id' => 'desc'],
                'kolom' => [
                    ['nama' => 'judul', 'label' => 'Judul'],
                    ['nama' => 'pemateri', 'label' => 'Pemateri'],
                    ['nama' => 'tanggal', 'label' => 'Tanggal', 'tipe' => 'tanggal'],
                    ['nama' => 'waktu_mulai', 'label' => 'Mulai'],
                    ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'saklar'],
                ],
                'field' => [
                    ['nama' => 'judul', 'label' => 'Judul kajian', 'tipe' => 'teks', 'wajib' => true, 'rules' => ['required', 'max:200'], 'lebar' => 'penuh'],
                    ['nama' => 'pemateri', 'label' => 'Pemateri', 'tipe' => 'teks', 'rules' => ['nullable', 'max:200']],
                    ['nama' => 'tema', 'label' => 'Tema', 'tipe' => 'teks', 'rules' => ['nullable', 'max:200']],
                    ['nama' => 'tanggal', 'label' => 'Tanggal', 'tipe' => 'tanggal', 'rules' => ['nullable', 'date']],
                    ['nama' => 'waktu_mulai', 'label' => 'Waktu mulai', 'tipe' => 'teks', 'rules' => ['nullable', 'max:10'], 'bantuan' => 'mis. 16.00'],
                    ['nama' => 'waktu_selesai', 'label' => 'Waktu selesai', 'tipe' => 'teks', 'rules' => ['nullable', 'max:10']],
                    ['nama' => 'tempat', 'label' => 'Tempat', 'tipe' => 'teks', 'rules' => ['nullable', 'max:200']],
                    ['nama' => 'rutin_mingguan', 'label' => 'Kajian rutin mingguan', 'tipe' => 'saklar', 'rules' => ['nullable', 'boolean']],
                    ['nama' => 'aktif', 'label' => 'Tampilkan di situs', 'tipe' => 'saklar', 'rules' => ['nullable', 'boolean']],
                    ['nama' => 'keterangan', 'label' => 'Keterangan', 'tipe' => 'teks-panjang', 'rules' => ['nullable'], 'baris' => 4, 'lebar' => 'penuh'],
                ],
            ],

            'program' => [
                'grup' => 'konten',
                'judul' => 'Program Sepekan',
                'judulSatu' => 'Program',
                'ikon' => 'halaman',
                'model' => Program::class,
                'keterangan' => 'Kegiatan rutin mingguan musholla.',
                'cari' => ['nama', 'hari', 'tempat'],
                'urut' => ['urutan' => 'asc', 'id' => 'asc'],
                'kolom' => [
                    ['nama' => 'nama', 'label' => 'Kegiatan'],
                    ['nama' => 'hari', 'label' => 'Hari'],
                    ['nama' => 'waktu', 'label' => 'Waktu'],
                    ['nama' => 'urutan', 'label' => 'Urutan', 'tipe' => 'angka'],
                    ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'saklar'],
                ],
                'field' => [
                    ['nama' => 'nama', 'label' => 'Nama kegiatan', 'tipe' => 'teks', 'wajib' => true, 'rules' => ['required', 'max:200'], 'lebar' => 'penuh'],
                    ['nama' => 'hari', 'label' => 'Hari', 'tipe' => 'pilihan', 'rules' => ['nullable'], 'opsi' => ['Ahad' => 'Ahad', 'Senin' => 'Senin', 'Selasa' => 'Selasa', 'Rabu' => 'Rabu', 'Kamis' => 'Kamis', 'Jumat' => 'Jumat', 'Sabtu' => 'Sabtu']],
                    ['nama' => 'waktu', 'label' => 'Waktu', 'tipe' => 'teks', 'rules' => ['nullable', 'max:30'], 'bantuan' => 'mis. 05.00 WIB'],
                    ['nama' => 'tempat', 'label' => 'Tempat', 'tipe' => 'teks', 'rules' => ['nullable', 'max:200']],
                    ['nama' => 'urutan', 'label' => 'Urutan', 'tipe' => 'angka', 'rules' => ['nullable', 'integer']],
                    ['nama' => 'aktif', 'label' => 'Tampilkan di situs', 'tipe' => 'saklar', 'rules' => ['nullable', 'boolean']],
                    ['nama' => 'keterangan', 'label' => 'Keterangan', 'tipe' => 'teks-panjang', 'rules' => ['nullable'], 'baris' => 3, 'lebar' => 'penuh'],
                ],
            ],

            'wakaf_program' => [
                'grup' => 'konten',
                'judul' => 'Program Wakaf',
                'judulSatu' => 'Program Wakaf',
                'ikon' => 'infaq',
                'model' => WakafProgram::class,
                'keterangan' => 'Program wakaf beserta target dan dana terkumpul.',
                'cari' => ['nama', 'keterangan'],
                'urut' => ['urutan' => 'asc', 'id' => 'asc'],
                'kolom' => [
                    ['nama' => 'nama', 'label' => 'Program'],
                    ['nama' => 'target', 'label' => 'Target', 'tipe' => 'uang'],
                    ['nama' => 'terkumpul', 'label' => 'Terkumpul', 'tipe' => 'uang'],
                    ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'saklar'],
                ],
                'field' => [
                    ['nama' => 'nama', 'label' => 'Nama program', 'tipe' => 'teks', 'wajib' => true, 'rules' => ['required', 'max:200'], 'lebar' => 'penuh'],
                    ['nama' => 'target', 'label' => 'Target dana (Rp)', 'tipe' => 'uang', 'rules' => ['nullable', 'numeric', 'min:0'], 'lebar' => 'penuh'],
                    ['nama' => 'terkumpul', 'label' => 'Dana terkumpul (Rp)', 'tipe' => 'uang', 'rules' => ['nullable', 'numeric', 'min:0'], 'lebar' => 'penuh'],
                    ['nama' => 'urutan', 'label' => 'Urutan', 'tipe' => 'angka', 'rules' => ['nullable', 'integer']],
                    ['nama' => 'aktif', 'label' => 'Tampilkan di situs', 'tipe' => 'saklar', 'rules' => ['nullable', 'boolean']],
                    ['nama' => 'keterangan', 'label' => 'Keterangan', 'tipe' => 'teks-panjang', 'rules' => ['nullable'], 'baris' => 5, 'lebar' => 'penuh'],
                    ['nama' => 'gambar_path', 'label' => 'Gambar', 'tipe' => 'berkas', 'rules' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'lebar' => 'penuh'],
                ],
            ],

            /* ================= KEUANGAN ================= */
            'kas' => [
                'grup' => 'keuangan',
                'judul' => 'Kas Musholla',
                'judulSatu' => 'Transaksi Kas',
                'ikon' => 'kas',
                'model' => Kas::class,
                'keterangan' => 'Catatan pemasukan & pengeluaran kas — jadi sumber halaman Laporan Keuangan.',
                'cari' => ['keterangan', 'kategori'],
                'urut' => ['tanggal' => 'desc', 'id' => 'desc'],
                'isiOtomatis' => ['dicatat_oleh' => 'user'],
                'kolom' => [
                    ['nama' => 'tanggal', 'label' => 'Tanggal', 'tipe' => 'tanggal'],
                    ['nama' => 'jenis', 'label' => 'Jenis', 'tipe' => 'lencana'],
                    ['nama' => 'kategori', 'label' => 'Kategori'],
                    ['nama' => 'keterangan', 'label' => 'Keterangan'],
                    ['nama' => 'jumlah', 'label' => 'Jumlah', 'tipe' => 'uang'],
                    ['nama' => 'bukti_path', 'label' => 'Bukti', 'tipe' => 'berkas'],
                ],
                'field' => [
                    ['nama' => 'tanggal', 'label' => 'Tanggal', 'tipe' => 'tanggal', 'wajib' => true, 'rules' => ['required', 'date']],
                    ['nama' => 'jenis', 'label' => 'Jenis', 'tipe' => 'pilihan', 'wajib' => true, 'rules' => ['required', 'in:masuk,keluar'], 'opsi' => ['masuk' => 'Pemasukan', 'keluar' => 'Pengeluaran']],
                    ['nama' => 'kategori', 'label' => 'Kategori', 'tipe' => 'teks', 'rules' => ['nullable', 'max:80'], 'bantuan' => 'mis. Infaq Jumat, Listrik, Kebersihan'],
                    ['nama' => 'jumlah', 'label' => 'Jumlah (Rp)', 'tipe' => 'uang', 'wajib' => true, 'rules' => ['required', 'numeric', 'min:0'], 'lebar' => 'penuh'],
                    ['nama' => 'keterangan', 'label' => 'Keterangan', 'tipe' => 'teks-panjang', 'rules' => ['nullable'], 'baris' => 3, 'lebar' => 'penuh'],
                    ['nama' => 'bukti_path', 'label' => 'Bukti (foto/nota)', 'tipe' => 'berkas', 'rules' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'], 'lebar' => 'penuh'],
                ],
            ],

            'infaq' => [
                'grup' => 'keuangan',
                'judul' => 'Infaq Masuk',
                'judulSatu' => 'Infaq',
                'ikon' => 'infaq',
                'model' => Infaq::class,
                'keterangan' => 'Infaq dari jamaah — verifikasi di sini agar tampil di laporan.',
                'cari' => ['nama_donatur', 'no_wa', 'keterangan'],
                'urut' => ['tanggal' => 'desc', 'id' => 'desc'],
                'kolom' => [
                    ['nama' => 'tanggal', 'label' => 'Tanggal', 'tipe' => 'tanggal'],
                    ['nama' => 'nama_donatur', 'label' => 'Donatur'],
                    ['nama' => 'nominal', 'label' => 'Nominal', 'tipe' => 'uang'],
                    ['nama' => 'tujuan', 'label' => 'Tujuan'],
                    ['nama' => 'status', 'label' => 'Status', 'tipe' => 'lencana'],
                    ['nama' => 'bukti_path', 'label' => 'Bukti', 'tipe' => 'berkas'],
                ],
                'field' => [
                    ['nama' => 'tanggal', 'label' => 'Tanggal', 'tipe' => 'tanggal', 'wajib' => true, 'rules' => ['required', 'date']],
                    ['nama' => 'nama_donatur', 'label' => 'Nama donatur', 'tipe' => 'teks', 'wajib' => true, 'rules' => ['required', 'max:200'], 'bantuan' => 'Boleh "Hamba Allah" bila tanpa nama.'],
                    ['nama' => 'no_wa', 'label' => 'Nomor WhatsApp', 'tipe' => 'teks', 'rules' => ['nullable', 'max:25']],
                    ['nama' => 'nominal', 'label' => 'Nominal (Rp)', 'tipe' => 'uang', 'wajib' => true, 'rules' => ['required', 'numeric', 'min:0'], 'lebar' => 'penuh'],
                    ['nama' => 'tujuan', 'label' => 'Tujuan', 'tipe' => 'teks', 'rules' => ['nullable', 'max:60'], 'bantuan' => 'mis. Kas umum, Wakaf, Yatim'],
                    ['nama' => 'status', 'label' => 'Status', 'tipe' => 'pilihan', 'wajib' => true, 'rules' => ['required', 'in:menunggu,terverifikasi,ditolak'], 'opsi' => ['menunggu' => 'Menunggu', 'terverifikasi' => 'Terverifikasi', 'ditolak' => 'Ditolak']],
                    ['nama' => 'keterangan', 'label' => 'Keterangan', 'tipe' => 'teks-panjang', 'rules' => ['nullable'], 'baris' => 3, 'lebar' => 'penuh'],
                    ['nama' => 'bukti_path', 'label' => 'Bukti transfer', 'tipe' => 'berkas', 'rules' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'], 'lebar' => 'penuh'],
                ],
            ],

            /* ================= JAMAAH ================= */
            'donatur' => [
                'grup' => 'jamaah',
                'judul' => 'Donatur',
                'judulSatu' => 'Donatur',
                'ikon' => 'akun',
                'model' => Donatur::class,
                'keterangan' => 'Daftar donatur tetap — dipakai untuk kabar kegiatan lewat WhatsApp.',
                'cari' => ['nama', 'no_wa', 'kategori'],
                'urut' => ['nama' => 'asc'],
                'kolom' => [
                    ['nama' => 'nama', 'label' => 'Nama'],
                    ['nama' => 'no_wa', 'label' => 'WhatsApp'],
                    ['nama' => 'kategori', 'label' => 'Kategori'],
                    ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'saklar'],
                ],
                'field' => [
                    ['nama' => 'nama', 'label' => 'Nama donatur', 'tipe' => 'teks', 'wajib' => true, 'rules' => ['required', 'max:200'], 'lebar' => 'penuh'],
                    ['nama' => 'no_wa', 'label' => 'Nomor WhatsApp', 'tipe' => 'teks', 'wajib' => true, 'rules' => ['required', 'max:25'], 'bantuan' => 'Format 08xxxxxxxxxx'],
                    ['nama' => 'kategori', 'label' => 'Kategori', 'tipe' => 'teks', 'rules' => ['nullable', 'max:80'], 'bantuan' => 'mis. Donatur tetap, Jamaah Jumat'],
                    ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'saklar', 'rules' => ['nullable', 'boolean']],
                    ['nama' => 'keterangan', 'label' => 'Keterangan', 'tipe' => 'teks-panjang', 'rules' => ['nullable'], 'baris' => 3, 'lebar' => 'penuh'],
                ],
            ],

            'users' => [
                'grup' => 'jamaah',
                'judul' => 'Pengguna',
                'judulSatu' => 'Pengguna',
                'ikon' => 'akun',
                'model' => User::class,
                'keterangan' => 'Akun subscriber hasil pendaftaran di situs, dan akun pengelola panel.',
                'cari' => ['name', 'nama_lengkap', 'email', 'no_wa'],
                'urut' => ['id' => 'desc'],
                'salin' => ['name' => 'nama_lengkap'],
                'kolom' => [
                    ['nama' => 'nama_lengkap', 'label' => 'Nama'],
                    ['nama' => 'email', 'label' => 'Email'],
                    ['nama' => 'no_wa', 'label' => 'WhatsApp'],
                    ['nama' => 'peran', 'label' => 'Peran', 'tipe' => 'lencana'],
                    ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'saklar'],
                ],
                'field' => [
                    ['nama' => 'nama_lengkap', 'label' => 'Nama lengkap', 'tipe' => 'teks', 'wajib' => true, 'rules' => ['required', 'max:200'], 'lebar' => 'penuh'],
                    ['nama' => 'email', 'label' => 'Email', 'tipe' => 'teks', 'wajib' => true, 'rules' => ['required', 'email', 'max:190'], 'lebar' => 'penuh'],
                    ['nama' => 'no_wa', 'label' => 'Nomor WhatsApp', 'tipe' => 'teks', 'rules' => ['nullable', 'max:25']],
                    ['nama' => 'peran', 'label' => 'Peran', 'tipe' => 'pilihan', 'wajib' => true, 'rules' => ['required', 'in:subscriber,admin'], 'opsi' => ['subscriber' => 'Subscriber — jamaah/anggota', 'admin' => 'Admin — boleh masuk panel']],
                    ['nama' => 'aktif', 'label' => 'Akun aktif', 'tipe' => 'saklar', 'rules' => ['nullable', 'boolean'], 'bantuan' => 'Akun nonaktif tidak bisa masuk.'],
                    ['nama' => 'password', 'label' => 'Sandi', 'tipe' => 'sandi', 'rules' => ['nullable', 'min:8'], 'bantuan' => 'Kosongkan bila tidak ingin mengganti. Minimal 8 huruf.'],
                ],
            ],

            /* ================= WHATSAPP ================= */
            'wa_template' => [
                'grup' => 'wa',
                'judul' => 'Template Pesan',
                'judulSatu' => 'Template Pesan',
                'ikon' => 'wa',
                'model' => WaTemplate::class,
                'keterangan' => 'Naskah pesan siap pakai untuk kabar kegiatan & pengingat infaq.',
                'cari' => ['judul', 'isi'],
                'urut' => ['id' => 'desc'],
                'kolom' => [
                    ['nama' => 'judul', 'label' => 'Judul'],
                    ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'saklar'],
                ],
                'field' => [
                    ['nama' => 'judul', 'label' => 'Judul template', 'tipe' => 'teks', 'wajib' => true, 'rules' => ['required', 'max:200'], 'lebar' => 'penuh'],
                    ['nama' => 'isi', 'label' => 'Isi pesan', 'tipe' => 'teks-panjang', 'wajib' => true, 'rules' => ['required'], 'baris' => 8, 'lebar' => 'penuh', 'bantuan' => 'Boleh memakai {nama} untuk nama donatur.'],
                    ['nama' => 'aktif', 'label' => 'Aktif', 'tipe' => 'saklar', 'rules' => ['nullable', 'boolean']],
                ],
            ],

            'wa_broadcast' => [
                'grup' => 'wa',
                'judul' => 'Riwayat Kirim',
                'judulSatu' => 'Riwayat Kirim',
                'ikon' => 'wa',
                'model' => WaBroadcast::class,
                'keterangan' => 'Catatan pengiriman pesan WhatsApp ke donatur.',
                'hanyaLihat' => true,
                'cari' => ['pesan_terkirim', 'status'],
                'urut' => ['id' => 'desc'],
                'kolom' => [
                    ['nama' => 'pesan_terkirim', 'label' => 'Pesan'],
                    ['nama' => 'jumlah_target', 'label' => 'Target', 'tipe' => 'angka'],
                    ['nama' => 'terkirim', 'label' => 'Terkirim', 'tipe' => 'angka'],
                    ['nama' => 'gagal', 'label' => 'Gagal', 'tipe' => 'angka'],
                    ['nama' => 'status', 'label' => 'Status', 'tipe' => 'lencana'],
                    ['nama' => 'mulai_at', 'label' => 'Mulai', 'tipe' => 'tanggal'],
                ],
                'field' => [],
            ],
        ];
    }

    /** Ambil definisi satu modul; batal 404 bila tidak ada. */
    public static function satu(string $nama): array
    {
        $semua = self::modul();
        abort_unless(isset($semua[$nama]), 404);

        return $semua[$nama];
    }

    /** Kelompok → daftar modul, untuk sidebar. */
    public static function menuSamping(): array
    {
        $keluar = [];
        foreach (self::modul() as $kunci => $m) {
            $keluar[$m['grup']][] = ['kunci' => $kunci] + $m;
        }

        return $keluar;
    }

    /**
     * Nilai satu kolom daftar untuk ditampilkan.
     *
     * @return array{0: string, 1: string} [teks, kelas lencana]
     */
    public static function nilaiKolom(mixed $rekaman, array $kolom): array
    {
        $nama = $kolom['nama'];
        $isi = data_get($rekaman, $nama);
        $tipe = $kolom['tipe'] ?? 'teks';

        return match ($tipe) {
            'tanggal' => [$isi ? \Illuminate\Support\Carbon::parse($isi)->translatedFormat('d M Y') : '—', ''],
            'uang' => ['Rp ' . number_format((float) $isi, 0, ',', '.'), ''],
            'angka' => [number_format((float) $isi), ''],
            'saklar' => [$isi ? 'Ya' : 'Tidak', $isi ? 'masuk' : 'abu'],
            'lencana' => self::lencana($nama, (string) $isi),
            default => [(string) ($isi ?? '—'), ''],
        };
    }

    /** Teks + warna lencana untuk kolom bertipe lencana. */
    private static function lencana(string $nama, string $nilai): array
    {
        return match ($nama) {
            'jenis' => [$nilai === 'masuk' ? 'Pemasukan' : 'Pengeluaran', $nilai],
            'status' => [
                ucfirst($nilai ?: '—'),
                match ($nilai) {
                    'terverifikasi', 'selesai' => 'masuk',
                    'ditolak', 'gagal' => 'merah',
                    'menunggu' => 'kuning',
                    default => 'abu',
                },
            ],
            'peran' => [$nilai === 'admin' ? 'Admin' : 'Subscriber', $nilai === 'admin' ? 'masuk' : 'abu'],
            default => [$nilai, ''],
        };
    }
}
