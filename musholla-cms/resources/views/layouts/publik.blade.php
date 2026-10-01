@php
    $namaSitus = $pengaturan['nama_situs'] ?? 'Musholla Al Karim';
    $slogan = $pengaturan['slogan'] ?? null;
    // Alamat publik: SATU-SATUNYA sumbernya tabel pengaturan (kunci alamat).
    $alamatSitus = trim((string) ($pengaturan['alamat'] ?? ''));
    $menuSitus = \App\Support\Menu::utama();
    // Nomor WhatsApp pengurus: dari pengaturan kunci kontak_wa (dipakai juga
    // halaman berinfaq). Hanya angka, seperti tautan wa.me di halaman lain.
    $waPengurus = preg_replace('/\D/', '', (string) ($pengaturan['kontak_wa'] ?? ''));
    $pengguna = auth()->user();
    $namaPengguna = $pengguna ? ($pengguna->nama_lengkap ?: $pengguna->name) : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', $namaSitus)</title>
    <meta name="description" content="@yield('deskripsi', ($slogan ? $slogan.' — ' : 'Musholla Al Karim — ').'kajian kitab hadis di Sampit, kajian rutin, pendidikan Al-Qur\'an, dan laporan kas yang terbuka.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="author" content="{{ $namaSitus }}">
    <meta name="keywords" content="Kajian Kitab Hadis Sampit, Kajian Kitab Hadis, Musholla Al Karim, Al Karim Islamic Center, kajian rutin Sampit, pendidikan Al-Qur'an, infaq dan wakaf, laporan kas terbuka">

    {{-- Pratinjau saat dibagikan: WhatsApp, Facebook, X, Telegram --}}
    <meta property="og:site_name" content="{{ $namaSitus }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="@yield('og_tipe', 'website')">
    <meta property="og:title" content="@yield('judul', $namaSitus)">
    <meta property="og:description" content="@yield('deskripsi', ($slogan ? $slogan.' — ' : 'Musholla Al Karim — ').'kajian kitab hadis di Sampit, kajian rutin, pendidikan Al-Qur\'an, dan laporan kas yang terbuka.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_gambar', \App\Services\KartuSosial::bawaan())">
    <meta property="og:image:secure_url" content="@yield('og_gambar', \App\Services\KartuSosial::bawaan())">
    <meta property="og:image:type" content="@yield('og_tipe_gambar', 'image/png')">
    <meta property="og:image:width" content="@yield('og_lebar', '1200')">
    <meta property="og:image:height" content="@yield('og_tinggi', '630')">
    <meta property="og:image:alt" content="@yield('judul', $namaSitus)">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('judul', $namaSitus)">
    <meta name="twitter:description" content="@yield('deskripsi', $slogan ?: 'Kajian rutin, pendidikan Al-Qur\'an, dan laporan kas yang terbuka.')">
    <meta name="twitter:image" content="@yield('og_gambar', \App\Services\KartuSosial::bawaan())">
    @hasSection('terbit_pada')
    <meta property="article:published_time" content="@yield('terbit_pada')">
    @endif
    <meta name="theme-color" content="#3f7d5c">
    <link rel="icon" href="/favicon-alkarim.ico" sizes="any">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16.png">
    <link rel="icon" type="image/png" sizes="48x48" href="/icons/favicon-48.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <style>
        :root {
            /* Warna khas Musholla Al Karim — hijau soft */
            --hijau: #3f7d5c;
            --hijau-tua: #2f6046;
            --hijau-lembut: #6aa383;
            --hijau-muda: #edf5f0;
            --hijau-garis: #dcebe2;
            --emas: #c9a961;
            --kertas: #f7faf8;
            --kartu: #ffffff;
            --garis: #e4ece7;
            --tinta: #26332c;
            --tinta-muda: #61736a;
            /* alias lama (dipakai sebagian halaman lain) */
            --navy: #2f6046;
            --navy-lembut: #457f61;
            --radius: 14px;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--tinta); background: var(--kertas); line-height: 1.7;
            -webkit-text-size-adjust: 100%;
        }
        body.menu-terbuka { overflow: hidden; }
        a { color: var(--hijau-tua); text-decoration: none; }
        a:hover { text-decoration: underline; }
        img { max-width: 100%; height: auto; border-radius: 12px; }
        .wadah { width: 100%; max-width: 1060px; margin: 0 auto; padding: 0 1.1rem; }
        svg { width: 1.05em; height: 1.05em; flex: none; }

        /* ================= kepala (baris ramping: hamburger + merek + aksi) =================
           Pola kursus: bilah atas memakai warna gelap merek situs + garis emas tipis
           di bawah. Di dalamnya HANYA tombol garis tiga (kiri), merek, lonceng
           notifikasi PWA, dan tombol akun. Nav horizontal lama DIHAPUS. */
        header.situs {
            background: linear-gradient(180deg, var(--hijau-tua) 0%, #20432f 100%);
            border-bottom: 1px solid var(--emas);
            position: sticky; top: 0; z-index: 40;
        }
        .kepala { display: flex; align-items: center; gap: .55rem; min-height: 60px; }
        .tombol-menu {
            border: 1px solid rgba(255,255,255,.34); background: rgba(255,255,255,.13); color: #fff;
            width: 38px; height: 38px; border-radius: 11px; cursor: pointer; padding: 0; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .tombol-menu:hover { background: rgba(255,255,255,.24); }
        .tombol-menu svg { width: 20px; height: 20px; }
        .merek {
            display: flex; align-items: center; gap: .55rem; margin-right: auto;
            color: #fff; min-width: 0;
        }
        .merek:hover { text-decoration: none; }
        .lambang {
            width: 36px; height: 36px; border-radius: 11px; flex: none;
            background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.30);
            display: inline-flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .lambang img { width: 100%; height: 100%; object-fit: contain; display: block; }
        .merek-teks { display: flex; flex-direction: column; line-height: 1.2; min-width: 0; }
        .merek-teks strong {
            font-size: 1rem; letter-spacing: .1px; color: #fff; white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis;
        }
        .merek-teks span {
            display: block; font-size: .68rem; color: var(--emas);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .kepala-aksi { display: flex; align-items: center; gap: .35rem; flex: none; }
        .tombol-akun {
            display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
            border: 1px solid rgba(255,255,255,.34); background: rgba(255,255,255,.13); color: #fff;
            width: 38px; height: 38px; border-radius: 11px; font-size: .85rem; flex: none;
        }
        .tombol-akun:hover { background: rgba(255,255,255,.24); text-decoration: none; }
        /* Layar sempit: perkecil merek & tombol supaya bilah atas tetap SATU baris
           tanpa gulir mendatar (cacat lama: 320px meluber 28px). */
        @media (max-width: 430px) {
            .kepala { gap: .4rem; min-height: 56px; }
            .merek { gap: .45rem; }
            .lambang { width: 32px; height: 32px; border-radius: 10px; }
            .merek-teks strong { font-size: .92rem; }
            .merek-teks span { font-size: .62rem; }
            .tombol-menu, .tombol-akun { width: 34px; height: 34px; }
            .kepala-aksi { gap: .25rem; }
        }
        @media (max-width: 360px) {
            .merek-teks span { display: none; }
        }

        /* ================= laci sidebar (SATU perilaku: desktop & HP) =================
           Pola sidebar kursus, warna merek Musholla Al Karim:
           gradasi gelap #2f6046 -> #20432f (hijau tua situs) + aksen emas #c9a961.
           Tersembunyi TOTAL saat tertutup di SEMUA ukuran layar. */
        :root { --sb-lebar: 272px; --sb-teks: #e7f1ea; --sb-muda: #9dc0ac; }
        body.menu-terbuka { overflow: hidden; }
        .sb-selubung {
            position: fixed; inset: 0; background: rgba(28, 44, 36, .5); backdrop-filter: blur(1.5px);
            opacity: 0; visibility: hidden; transition: opacity .22s ease; z-index: 65;
        }
        body.menu-terbuka .sb-selubung { opacity: 1; visibility: visible; }
        .sb-sisi {
            position: fixed; top: 0; left: 0; bottom: 0; width: var(--sb-lebar); max-width: 86vw; z-index: 70;
            background: linear-gradient(180deg, var(--hijau-tua) 0%, #20432f 100%);
            color: var(--sb-teks); display: flex; flex-direction: column;
            border-right: 1px solid rgba(201, 169, 97, .55);
            transform: translateX(-102%); transition: transform .24s ease;
            box-shadow: 4px 0 22px rgba(24, 52, 38, .18);
        }
        body.menu-terbuka .sb-sisi { transform: translateX(0); }
        /* bilah merek di atas laci */
        .sb-merek {
            display: flex; align-items: center; gap: .6rem; flex: none;
            padding: .9rem .95rem .85rem; border-bottom: 1px solid rgba(255,255,255,.12);
        }
        .sb-merek .lambang { width: 40px; height: 40px; border-radius: 12px; }
        .sb-merek .merek-teks { margin-right: auto; }
        .sb-merek .merek-teks strong { font-size: .95rem; }
        .sb-merek .merek-teks span { font-size: .68rem; color: var(--sb-muda); letter-spacing: .3px; }
        .sb-tutup {
            flex: none; background: rgba(255,255,255,.12); border: 0; color: #fff;
            width: 32px; height: 32px; border-radius: 10px; cursor: pointer; font-size: 1rem; line-height: 1;
        }
        .sb-tutup:hover { background: rgba(255,255,255,.24); }
        /* daftar menu */
        .sb-nav { flex: 1; overflow-y: auto; padding: .6rem .6rem .7rem; }
        .sb-nav .sb-judul {
            margin: .2rem .7rem .4rem; font-size: .66rem; letter-spacing: 1px; text-transform: uppercase;
            color: var(--sb-muda); font-weight: 700;
        }
        .sb-nav a {
            display: flex; align-items: center; gap: .6rem; padding: .5rem .7rem; border-radius: 10px;
            color: #dcebe2; font-size: .89rem; text-decoration: none; margin-bottom: .1rem;
            border-left: 3px solid transparent;
        }
        .sb-nav a:hover { background: rgba(255,255,255,.09); color: #fff; text-decoration: none; }
        .sb-nav a.sb-aktif {
            background: rgba(255,255,255,.16); color: #fff; font-weight: 700;
            border-left-color: var(--emas);
        }
        .sb-nav a svg { width: 18px; height: 18px; color: var(--sb-muda); opacity: .95; }
        .sb-nav a.sb-aktif svg { color: var(--emas); }
        /* kaki laci: ajakan + alamat + WhatsApp */
        .sb-kaki {
            flex: none; border-top: 1px solid rgba(255,255,255,.12);
            padding: .8rem .85rem .9rem; display: flex; flex-direction: column; gap: .45rem;
        }
        .sb-kaki .sb-infaq {
            display: flex; align-items: center; justify-content: center; gap: .45rem;
            padding: .68rem .7rem; border-radius: 11px; font-size: .92rem; font-weight: 800;
            text-decoration: none; color: #243a2c;
            background: linear-gradient(180deg, #d9c07a, var(--emas));
            box-shadow: 0 8px 18px -10px rgba(201, 169, 97, .95);
        }
        .sb-kaki .sb-infaq:hover { filter: brightness(1.06); text-decoration: none; }
        .sb-kaki .sb-infaq svg { width: 17px; height: 17px; }
        .sb-kaki .sb-akun { display: flex; gap: .4rem; }
        .sb-kaki .sb-akun a, .sb-kaki .sb-akun button {
            flex: 1; display: flex; align-items: center; justify-content: center; gap: .35rem;
            padding: .48rem .5rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
            font-family: inherit; text-align: center; text-decoration: none;
            background: rgba(255,255,255,.12); color: #fff; border: 0; cursor: pointer; width: 100%;
        }
        .sb-kaki .sb-akun a:hover, .sb-kaki .sb-akun button:hover { background: rgba(255,255,255,.24); text-decoration: none; }
        .sb-kaki .sb-kecil { border-top: 1px solid rgba(255,255,255,.12); padding-top: .6rem; }
        .sb-kaki .sb-alamat { margin: 0; font-size: .73rem; line-height: 1.5; color: var(--sb-muda); overflow-wrap: anywhere; }
        .sb-kaki .sb-wa {
            display: inline-flex; align-items: center; gap: .4rem; margin-top: .4rem;
            font-size: .78rem; font-weight: 600; color: var(--emas); text-decoration: none;
        }
        .sb-kaki .sb-wa:hover { color: #e3cf94; text-decoration: none; }
        .sb-kaki .sb-wa svg { width: 15px; height: 15px; }

        /* ================= isi ================= */
        main { padding: 1.5rem 0 3rem; }
        .pahlawan {
            background: linear-gradient(140deg, var(--hijau) 0%, var(--hijau-lembut) 100%);
            color: #fff; border-radius: 18px; padding: 1.9rem 1.4rem; margin-bottom: 1.6rem;
        }
        .pahlawan h1 { margin: 0 0 .5rem; font-size: 1.5rem; line-height: 1.3; }
        .pahlawan p { margin: 0 0 .55rem; opacity: .94; font-size: .95rem; }
        /* Alamat musholla di kepala beranda: teks kecil dengan jarak POSITIF
           (dulu memakai margin negatif -.65rem sehingga rapat menabrak slogan). */
        .pahlawan .alamat-singkat { margin: 0 0 1rem; font-size: .75rem; opacity: .9; }
        .aksi { display: flex; flex-wrap: wrap; gap: .55rem; }
        .tombol {
            display: inline-block; padding: .5rem .95rem; border-radius: 10px; font-size: .87rem;
            font-weight: 600; background: #fff; color: var(--hijau-tua);
        }
        .tombol.garis { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.6); }
        .tombol:hover { text-decoration: none; opacity: .93; }

        h2.bagian {
            font-size: 1.1rem; margin: 2rem 0 .9rem; color: var(--hijau-tua);
            display: flex; align-items: center; gap: .5rem;
        }
        h2.bagian::before { content: ""; width: 4px; height: 1.05em; border-radius: 3px; background: var(--hijau-lembut); }
        .kartu {
            background: var(--kartu); border: 1px solid var(--garis); border-radius: var(--radius);
            padding: 1.15rem 1.2rem; box-shadow: 0 1px 2px rgba(47, 96, 70, .05);
        }
        .jaring { display: grid; gap: 1rem; }
        @media (min-width: 700px) { .jaring.dua { grid-template-columns: 1fr 1fr; } .jaring.tiga { grid-template-columns: repeat(3, 1fr); } }
        .kartu h3 { margin: .1rem 0 .35rem; font-size: 1rem; color: var(--hijau-tua); }
        .kartu .tanggal { font-size: .74rem; color: var(--tinta-muda); text-transform: uppercase; letter-spacing: .4px; }
        .kartu p { margin: .4rem 0 0; font-size: .92rem; color: var(--tinta-muda); }

        .isi-halaman :first-child { margin-top: 0; }
        .isi-halaman h1, .isi-halaman h2, .isi-halaman h3 { color: var(--hijau-tua); line-height: 1.35; }
        .isi-halaman h2 { font-size: 1.15rem; margin: 1.6rem 0 .6rem; }
        .isi-halaman h3 { font-size: 1.02rem; margin: 1.3rem 0 .5rem; }
        .isi-halaman p { margin: .7rem 0; }
        .isi-halaman img { margin: .6rem 0; }
        .isi-halaman ul, .isi-halaman ol { padding-left: 1.2rem; }
        .isi-halaman table { width: 100%; border-collapse: collapse; font-size: .9rem; display: block; overflow-x: auto; }
        .isi-halaman th, .isi-halaman td { border: 1px solid var(--garis); padding: .5rem .6rem; text-align: left; }

        .remah { font-size: .8rem; color: var(--tinta-muda); margin-bottom: .7rem; }
        .judul-halaman { font-size: 1.35rem; color: var(--hijau-tua); margin: .2rem 0 1.1rem; }

        /* ================= formulir ================= */
        .form-kartu .baris { margin-bottom: .9rem; }
        .form-kartu label, .label { display: block; font-size: .82rem; font-weight: 600; color: var(--hijau-tua); margin-bottom: .3rem; }
        .form-kartu .wajib { color: #a4373f; }
        .form-kartu input, .form-kartu select, .form-kartu textarea {
            width: 100%; font: inherit; font-size: .92rem; padding: .55rem .7rem;
            border: 1px solid var(--garis); border-radius: 10px; background: #fff; color: var(--tinta);
        }
        .form-kartu input[type=file] { padding: .45rem; background: #fbfcfd; }
        .form-kartu input:focus, .form-kartu select:focus, .form-kartu textarea:focus {
            outline: 2px solid rgba(63, 125, 92, .22); border-color: var(--hijau);
        }
        .form-kartu small { display: block; font-size: .74rem; color: var(--tinta-muda); margin-top: .25rem; }
        .tombol-kirim {
            font: inherit; font-weight: 600; font-size: .9rem; padding: .6rem 1.2rem; border: 0;
            border-radius: 10px; background: var(--hijau); color: #fff; cursor: pointer;
        }
        .tombol-kirim:hover { background: var(--hijau-tua); }
        .pesan-sukses {
            background: #e9f6ee; border: 1px solid #bfe3cb; color: #1f5a37;
            padding: .9rem 1.1rem; border-radius: 12px; margin-bottom: 1.2rem; font-size: .9rem;
        }
        .pesan-galat {
            background: #fdeced; border: 1px solid #f2c3c6; color: #8c2b33;
            padding: .9rem 1.1rem; border-radius: 12px; margin-bottom: 1.2rem; font-size: .9rem;
        }
        .kosong { color: var(--tinta-muda); font-style: italic; }
        .tombol-kecil {
            display: inline-flex; align-items: center; gap: .3rem; font-size: .8rem; font-weight: 600;
            padding: .35rem .7rem; border-radius: 9px; background: var(--hijau-muda); color: var(--hijau-tua);
            white-space: nowrap;
        }
        .tombol-kecil:hover { background: #dcece3; text-decoration: none; }

        /* ================= kabar terbaru (grid 3 kolom / alir di HP) ================= */
        .cari-berita { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: .9rem; }
        .cari-berita input[type=search] {
            font: inherit; font-size: .9rem; padding: .5rem .75rem; border: 1px solid var(--garis);
            border-radius: 10px; background: #fff; flex: 1 1 14rem; min-width: 0;
        }
        .chip-baris { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1.2rem; }
        .chip-kategori {
            display: inline-block; font-size: .78rem; font-weight: 600; padding: .25rem .65rem; border-radius: 999px;
            background: var(--kartu); border: 1px solid var(--garis); color: var(--tinta-muda);
        }
        .chip-kategori:hover { background: var(--hijau-muda); text-decoration: none; }
        .chip-kategori.aktif { background: var(--hijau); border-color: var(--hijau); color: #fff; }
        .halaman { display: flex; justify-content: center; align-items: center; gap: .35rem; margin-top: 1.3rem; flex-wrap: wrap; }
        .halaman a, .halaman span {
            min-width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid var(--garis); border-radius: 9px; background: #fff; font-size: .84rem;
            color: var(--tinta-muda); padding: 0 .6rem;
        }
        .halaman a:hover { background: var(--hijau-muda); text-decoration: none; }
        .halaman .aktif { background: var(--hijau); border-color: var(--hijau); color: #fff; font-weight: 600; }
        .berita-alir { margin-bottom: 1.8rem; }
        .berita-alir-jalur {
            display: flex; gap: .9rem; overflow-x: auto; scroll-snap-type: x mandatory;
            padding-bottom: .5rem; -webkit-overflow-scrolling: touch; scrollbar-width: none;
        }
        .berita-alir-jalur::-webkit-scrollbar { display: none; }
        .berita-kartu { flex: 0 0 min(84%, 330px); scroll-snap-align: center; }
        .berita-kartu-tautan {
            display: flex; flex-direction: column; height: 100%;
            background: var(--kartu); border: 1px solid var(--garis); border-radius: var(--radius);
            overflow: hidden; box-shadow: 0 1px 2px rgba(47, 96, 70, .05); color: inherit;
        }
        .berita-kartu-tautan:hover { text-decoration: none; border-color: var(--hijau-lembut); }
        .berita-gambar { position: relative; display: block; aspect-ratio: 1 / 1; background: var(--hijau-muda); overflow: hidden; }
        .berita-gambar img { width: 100%; height: 100%; object-fit: cover; border-radius: 0; display: block; }
        .berita-gambar-kosong { display: flex; align-items: center; justify-content: center; height: 100%; color: var(--hijau-lembut); }
        .berita-gambar-kosong svg { width: 34%; height: 34%; }
        .berita-chip {
            position: absolute; left: .6rem; top: .6rem; font-size: .66rem; font-weight: 700; letter-spacing: .5px;
            text-transform: uppercase; background: rgba(255, 255, 255, .94); color: var(--hijau-tua);
            padding: .2rem .55rem; border-radius: 999px;
        }
        .berita-isi { display: block; padding: .85rem .95rem 1rem; }
        .berita-isi h3 { margin: .2rem 0 .35rem; font-size: .96rem; color: var(--hijau-tua); line-height: 1.4; }
        .berita-ringkas { display: block; font-size: .85rem; color: var(--tinta-muda); line-height: 1.55; }
        .berita-titik { display: flex; gap: .35rem; justify-content: center; margin-top: .3rem; }
        .berita-titik button {
            width: 7px; height: 7px; padding: 0; border: 0; border-radius: 999px; cursor: pointer;
            background: var(--hijau-garis);
        }
        .berita-titik button.aktif { background: var(--hijau); width: 18px; }
        @media (min-width: 900px) {
            .berita-alir-jalur { display: grid; grid-template-columns: repeat(3, 1fr); overflow: visible; }
            .berita-kartu { flex: none; }
            .berita-titik { display: none; }
        }

        /* ================= jadwal program sepekan ================= */
        .bagian-ket { font-size: .86rem; color: var(--tinta-muda); margin: -.4rem 0 1rem; }
        .jaring.jadwal { grid-template-columns: 1fr; }
        @media (min-width: 620px) { .jaring.jadwal { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1020px) { .jaring.jadwal { grid-template-columns: repeat(3, 1fr); } }
        .jadwal-kartu { padding: 1rem 1.05rem; }
        .jadwal-ini { border-color: var(--hijau-lembut); box-shadow: 0 0 0 2px rgba(63, 125, 92, .13); }
        .jadwal-kepala { display: flex; align-items: center; gap: .5rem; margin-bottom: .45rem; }
        .jadwal-kepala h3 { margin: 0; font-size: .97rem; color: var(--hijau-tua); }
        .chip-hari {
            font-size: .66rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
            background: var(--hijau); color: #fff; padding: .15rem .5rem; border-radius: 999px;
        }
        .jadwal-item { display: flex; gap: .55rem; align-items: flex-start; padding: .45rem 0; border-top: 1px dashed var(--garis); }
        .jadwal-item:first-of-type { border-top: 0; }
        .waktu-chip {
            flex: none; font-size: .7rem; font-weight: 700; color: var(--hijau-tua);
            background: var(--hijau-muda); border-radius: 8px; padding: .2rem .45rem; line-height: 1.35;
        }
        .jadwal-nama { font-size: .89rem; color: var(--tinta); line-height: 1.5; }
        .jadwal-kosong { font-size: .82rem; color: var(--tinta-muda); font-style: italic; margin: .3rem 0 0; }
        @media (max-width: 430px) {
            .jadwal-item { flex-direction: column; gap: .25rem; }
        }

        /* ================= infaq: popup & tombol melayang ================= */
        .infaq-selubung {
            position: fixed; inset: 0; background: rgba(28, 44, 36, .48); z-index: 80;
            opacity: 0; visibility: hidden; transition: opacity .2s ease;
        }
        .infaq-modal {
            position: fixed; z-index: 90; left: 50%; top: 50%; transform: translate(-50%, -46%) scale(.98);
            width: min(94vw, 520px); max-height: 88vh; overflow-y: auto; background: #fff;
            border-radius: 18px; box-shadow: 0 18px 50px rgba(20, 42, 30, .28);
            opacity: 0; visibility: hidden; transition: opacity .2s ease, transform .22s ease;
        }
        body.infaq-terbuka { overflow: hidden; }
        body.infaq-terbuka .infaq-selubung { opacity: 1; visibility: visible; }
        body.infaq-terbuka .infaq-modal { opacity: 1; visibility: visible; transform: translate(-50%, -50%) scale(1); }
        .infaq-kepala {
            display: flex; align-items: center; gap: .6rem; padding: .95rem 1.1rem;
            border-bottom: 1px solid var(--hijau-garis); position: sticky; top: 0; background: #fff; z-index: 2;
        }
        .infaq-kepala h3 { margin: 0; margin-right: auto; font-size: 1.02rem; color: var(--hijau-tua); }
        .infaq-tutup {
            border: 1px solid var(--hijau-garis); background: #fff; color: var(--tinta-muda);
            width: 34px; height: 34px; border-radius: 10px; cursor: pointer; font-size: 1rem; line-height: 1;
        }
        .infaq-tutup:hover { background: var(--hijau-muda); }
        .infaq-isi { padding: 1rem 1.1rem 1.3rem; }
        .infaq-label { font-size: .72rem; letter-spacing: .7px; text-transform: uppercase; color: var(--tinta-muda); margin: 0 0 .35rem; }
        .infaq-bank {
            border: 1px solid var(--hijau-garis); border-radius: 14px; padding: .9rem 1rem; margin-bottom: 1rem;
            background: linear-gradient(160deg, #f4faf6, #ffffff);
        }
        .infaq-bank .bank { font-size: .84rem; color: var(--hijau-tua); font-weight: 600; }
        .infaq-bank .nomor {
            font-size: 1.42rem; font-weight: 700; letter-spacing: 1px; color: var(--tinta);
            font-variant-numeric: tabular-nums; margin: .2rem 0 .1rem; overflow-wrap: anywhere;
        }
        .infaq-bank .atas { font-size: .84rem; color: var(--tinta-muda); }
        .infaq-bank .baris-salin { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; margin-top: .7rem; }
        .tombol-salin {
            font: inherit; font-size: .84rem; font-weight: 600; padding: .42rem .8rem; border-radius: 10px;
            border: 1px solid var(--hijau); background: var(--hijau); color: #fff; cursor: pointer;
        }
        .tombol-salin.sudah { background: #1f6b41; border-color: #1f6b41; }
        .infaq-qris { text-align: center; border: 1px solid var(--hijau-garis); border-radius: 14px; padding: .9rem; margin-bottom: 1rem; }
        .infaq-qris img { width: 100%; max-width: 260px; border-radius: 12px; background: #fff; }
        .infaq-qris .ket { font-size: .82rem; color: var(--tinta-muda); margin: .5rem 0 0; }
        .infaq-melayang {
            position: fixed; z-index: 70; right: 1rem; bottom: 1rem; display: none;
            align-items: center; gap: .45rem; font: inherit; font-size: .9rem; font-weight: 600;
            padding: .62rem 1.05rem; border: 0; border-radius: 999px; cursor: pointer;
            background: var(--hijau); color: #fff; box-shadow: 0 8px 22px rgba(31, 96, 70, .35);
        }
        .infaq-melayang:active { transform: scale(.97); }
        @media (max-width: 760px) { html.js .infaq-melayang { display: inline-flex; } }

        /* tombol pemicu popup hanya berguna bila JavaScript hidup */
        .pemicu-infaq { display: none; }
        html.js .pemicu-infaq { display: inline-flex; }

        /* tombol tutup popup hanya perlu saat popup dipakai */
        .infaq-modal .tombol-tutup-popup { display: none; }
        html.js .infaq-modal .tombol-tutup-popup { display: inline-flex; }

        /* catatan bila JavaScript dimatikan (formulir hidup di dalam popup) */
        .catatan-tanpa-js { display: block; }
        html.js .catatan-tanpa-js { display: none; }

        /* pemilih nominal cepat di popup */
        .nominal-cepat { display: flex; flex-wrap: wrap; gap: .4rem; margin: .35rem 0 .6rem; }
        .nominal-cepat button {
            font: inherit; font-size: .82rem; font-weight: 600; padding: .35rem .7rem; border-radius: 999px;
            border: 1px solid var(--hijau-garis); background: var(--hijau-muda); color: var(--hijau-tua); cursor: pointer;
        }
        .nominal-cepat button.aktif { background: var(--hijau); border-color: var(--hijau); color: #fff; }

        /* ================= kaki ================= */
        footer.situs { background: var(--hijau-tua); color: #dbe9e1; padding: 2rem 0 2.2rem; font-size: .87rem; }
        footer.situs a { color: #fff; }
        .kaki-jaring { display: grid; gap: 1.4rem; }
        @media (min-width: 780px) { .kaki-jaring { grid-template-columns: 1.3fr 1fr 1fr; gap: 2rem; } }
        footer.situs h4 { margin: 0 0 .6rem; font-size: .8rem; letter-spacing: .8px; text-transform: uppercase; opacity: .78; }
        footer.situs .tautan { display: flex; flex-direction: column; gap: .4rem; }
        footer.situs .tautan a { font-size: .88rem; opacity: .95; }
        footer.situs .kecil { opacity: .75; font-size: .78rem; margin-top: 1.6rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,.16); }
        /* Alamat musholla di kaki situs: teks kecil, maksimal dua baris sampai lebar 320px. */
        footer.situs .kaki-alamat { margin: .45rem 0 0; font-size: .75rem; line-height: 1.5; opacity: .86; }
    
        /* --- tombol bagikan ke WhatsApp: ikon kecil, pojok kanan bawah kartu --- */
        .berita-kartu, .jadwal-kartu, .kartu-bagikan { position: relative; }
        .berita-kartu .berita-isi { padding-bottom: 2.2rem; }
        .jadwal-kartu { padding-bottom: 2.7rem; }
        .bagikan-wa-atas { top: .8rem; right: .8rem; bottom: auto; z-index: 4; }
        .kartu-bagikan { padding-bottom: 1.1rem; }
        .bagikan-wa {
            position: absolute; right: .6rem; bottom: .6rem; z-index: 3;
            width: 30px; height: 30px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            background: #25d366; color: #fff; box-shadow: 0 1px 3px rgba(20, 60, 40, .25);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .bagikan-wa:hover { transform: scale(1.07); box-shadow: 0 2px 7px rgba(20, 60, 40, .32); text-decoration: none; }
        .bagikan-wa svg { width: 17px; height: 17px; }

        /* gambar program wakaf di halaman pembaca */
        .wakaf-gambar {
            display: block; width: 100%; aspect-ratio: 1 / 1; object-fit: cover;
            border-radius: 10px; margin: -.35rem 0 .8rem; background: var(--hijau-muda);
        }
        .wakaf-barang { font-size: .84rem; color: var(--hijau-tua); font-weight: 600; margin: .1rem 0 .5rem; }
        .kartu-wakaf { position: relative; padding-bottom: 3.4rem; }
        /* lonceng notifikasi PWA */
        .tombol-lonceng { position: relative; background: 0; border: 0; cursor: pointer; color: var(--hijau-tua);
            width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center;
            justify-content: center; transition: background .15s ease; }
        .tombol-lonceng:hover { background: var(--hijau-muda); }
        .tombol-lonceng svg { width: 19px; height: 19px; }
        .tombol-lonceng.nyala::after { content: ''; position: absolute; top: 5px; right: 5px; width: 8px; height: 8px;
            border-radius: 50%; background: #25d366; border: 2px solid #fff; }
        .notif-pesan { position: fixed; left: 50%; bottom: 1.1rem; transform: translateX(-50%); background: var(--hijau-tua);
            color: #fff; font-size: .84rem; padding: .55rem .95rem; border-radius: 999px; z-index: 70;
            opacity: 0; transition: opacity .25s ease; pointer-events: none; max-width: 92vw; text-align: center; }
        .notif-pesan.tampil { opacity: 1; }

        /* ---- tabel rincian kebutuhan program (infaq & wakaf) ---- */
        .rincian-kepala { display: flex; align-items: baseline; gap: .6rem; flex-wrap: wrap; margin: 1.2rem 0 .55rem; }
        .rincian-kepala h2 { margin: 0; }
        .rincian-periode { font-size: .78rem; color: var(--tinta-muda); }
        .rincian-tabel { width: 100%; border-collapse: collapse; font-size: .9rem; }
        .rincian-tabel caption { text-align: left; font-size: .82rem; color: var(--tinta-muda); padding: 0 0 .5rem; }
        .rincian-tabel th, .rincian-tabel td { padding: .55rem .6rem; border-bottom: 1px solid var(--garis); vertical-align: top; }
        .rincian-tabel thead th {
            font-size: .7rem; letter-spacing: .05em; text-transform: uppercase; color: var(--tinta-muda);
            font-weight: 600; border-bottom: 1px solid var(--hijau-garis); white-space: nowrap;
        }
        .rincian-tabel tbody th { text-align: left; font-weight: 600; color: var(--tinta); }
        .rincian-tabel td.kanan, .rincian-tabel th.kanan { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .rincian-tabel tbody tr:nth-child(even) { background: #f8fbf9; }
        .rincian-tabel tfoot td {
            border-bottom: 0; border-top: 2px solid var(--hijau-garis);
            font-weight: 700; color: var(--hijau-tua); font-size: .95rem; padding-top: .6rem;
        }
        .rincian-catatan { display: block; font-weight: 400; font-size: .78rem; color: var(--tinta-muda); margin-top: .15rem; }
        /* di layar sempit: "Jumlah" & "Harga satuan" naik ke bawah nama kebutuhan */
        @media (max-width: 560px) {
            .rincian-tabel thead { display: none; }
            .rincian-tabel tr { display: grid; grid-template-columns: 1fr auto; gap: 0 .6rem; padding: .6rem 0; border-bottom: 1px solid var(--garis); }
            .rincian-tabel tbody th { grid-column: 1 / -1; }
            .rincian-tabel td { border: 0; padding: 0; font-size: .84rem; color: var(--tinta-muda); }
            .rincian-tabel td.kanan:last-child { font-weight: 700; color: var(--hijau-tua); }
            .rincian-tabel td.kanan:not(:last-child)::after { content: ''; }
            .rincian-tabel tfoot tr { display: grid; grid-template-columns: 1fr auto; border-bottom: 0; }
            .rincian-tabel tfoot td { border: 0; padding: .55rem 0 0; }
            .rincian-tabel tfoot td:first-child { border-top: 2px solid var(--hijau-garis); }
            .rincian-tabel tfoot td:last-child { border-top: 2px solid var(--hijau-garis); text-align: right; }
        }
    </style>
    @stack('gaya')
<script type="application/ld+json">
{!! json_encode([
    '@'.'context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'PlaceOfWorship',
            '@id' => url('/').'#musholla',
            'name' => $namaSitus,
            'alternateName' => 'Al Karim Islamic Center',
            'url' => url('/'),
            'hasMap' => 'https://maps.app.goo.gl/NFF6yzNhAWXwJMS68?g_st=ac',
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Jalan Cilik Riwut KM 5, Perum. Mentaya Permai Blok D - No. 5', 'addressLocality' => 'Baamang, Sampit', 'addressRegion' => 'Kalimantan Tengah', 'addressCountry' => 'ID'],
            'description' => 'Musholla Al Karim di Sampit, Kalimantan Tengah: kajian kitab hadis dan kajian rutin, pendidikan Al-Qur\'an, infaq dan wakaf, serta laporan kas yang terbuka.',
            'knowsAbout' => ['Kajian kitab hadis', 'Kajian rutin', 'Pendidikan Al-Qur\'an', 'Infaq dan wakaf'],
        ],
        [
            '@type' => 'WebSite',
            'url' => url('/'),
            'name' => $namaSitus,
            'inLanguage' => 'id-ID',
            'publisher' => ['@id' => url('/').'#musholla'],
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
</head>
<body>

<script>document.documentElement.classList.add('js');</script>

<header class="situs">
    <div class="wadah kepala">
        <button type="button" class="tombol-menu" id="tombolMenu" aria-label="Buka menu" aria-expanded="false" aria-controls="sisiMenu" onclick="bukaMenu()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>

        <a href="/" class="merek">
            <span class="lambang"><img src="/icons/emblem-256.png" alt="Lambang Al Karim Islamic Center" width="36" height="36"></span>
            <span class="merek-teks">
                <strong>{{ $namaSitus }}</strong>
                @if ($slogan) <span>{{ $slogan }}</span> @endif
            </span>
        </a>

        <div class="kepala-aksi">
            <button type="button" class="tombol-lonceng" id="loncengNotif" hidden
                    title="Nyalakan notifikasi tulisan baru" aria-label="Nyalakan notifikasi tulisan baru">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7"/><path d="M13.7 20a2 2 0 0 1-3.4 0"/>
                </svg>
            </button>
            @auth
                <a href="{{ url('/anggota') }}" class="tombol-akun" title="Akun saya" aria-label="Akun saya">
                    @include('publik._ikon', ['nama' => 'akun'])
                </a>
            @else
                <a href="{{ url('/masuk') }}" class="tombol-akun" title="Masuk" aria-label="Masuk">
                    @include('publik._ikon', ['nama' => 'akun'])
                </a>
            @endauth
        </div>
    </div>
</header>

{{-- ============ LACI SIDEBAR ============
     Satu perilaku untuk desktop & HP: tersembunyi penuh saat tertutup,
     meluncur dari kiri saat dibuka, ditutup lewat X / klik selubung / Esc. --}}
<div class="sb-selubung" id="selubungMenu" onclick="tutupMenu()"></div>
<aside class="sb-sisi" id="sisiMenu" aria-label="Menu situs" aria-hidden="true">
    <div class="sb-merek">
        <span class="lambang"><img src="/icons/emblem-256.png" alt="" width="40" height="40"></span>
        <span class="merek-teks">
            <strong>{{ $namaSitus }}</strong>
            @if ($slogan) <span>{{ $slogan }}</span> @endif
        </span>
        <button type="button" class="sb-tutup" aria-label="Tutup menu" onclick="tutupMenu()">&#10005;</button>
    </div>

    <nav class="sb-nav" aria-label="Menu utama">
        <p class="sb-judul">Menu</p>
        @foreach ($menuSitus as $m)
            <a href="{{ $m['url'] }}" @if (! empty($m['luar'])) target="_blank" rel="noopener" @endif @class(['sb-aktif' => request()->is($m['aktif'])])>
                @include('publik._ikon', ['nama' => $m['ikon']])
                {{ $m['judul'] }}
            </a>
        @endforeach
    </nav>

    <div class="sb-kaki">
        <a href="{{ url('/mari-berinfaq') }}" class="sb-infaq">
            @include('publik._ikon', ['nama' => 'infaq'])
            Mari Berinfaq
        </a>

        <div class="sb-akun">
            @auth
                <a href="{{ url('/anggota') }}">Akun Saya</a>
                <form method="post" action="{{ url('/keluar') }}" style="flex:1;margin:0">
                    @csrf
                    <button type="submit">Keluar</button>
                </form>
            @else
                <a href="{{ url('/masuk') }}">Masuk</a>
                <a href="{{ url('/daftar') }}">Daftar</a>
            @endauth
        </div>

        <div class="sb-kecil">
            @if ($alamatSitus) <p class="sb-alamat">{{ $alamatSitus }}</p> @endif
            @if ($waPengurus)
                <a class="sb-wa" href="https://wa.me/{{ $waPengurus }}" target="_blank" rel="noopener">
                    @include('publik._ikon', ['nama' => 'wa'])
                    WhatsApp pengurus
                </a>
            @endif
        </div>
    </div>
</aside>

<main>
    <div class="wadah">
        @yield('isi')
    </div>
</main>

<footer class="situs">
    <div class="wadah">
        <div class="kaki-jaring">
            <div>
                <h4>{{ $namaSitus }}</h4>
                @if ($slogan) <p style="margin:0;opacity:.9">{{ $slogan }}</p> @endif
                @if ($alamatSitus) <p class="kaki-alamat">{{ $alamatSitus }}</p> @endif
                <div class="tautan" style="margin-top:.5rem">
                    <a href="https://maps.app.goo.gl/NFF6yzNhAWXwJMS68?g_st=ac" target="_blank" rel="noopener">Lokasi Musholla</a>
                </div>
            </div>
            <div>
                <h4>Menu</h4>
                <div class="tautan">
                    @foreach ($menuSitus as $m)
                        <a href="{{ $m['url'] }}"@if (! empty($m['luar'])) target="_blank" rel="noopener"@endif>{{ $m['judul'] }}</a>
                    @endforeach
                </div>
            </div>
            <div>
                <h4>Akun</h4>
                <div class="tautan">
                    @auth
                        <a href="{{ url('/anggota') }}">Akun Saya</a>
                        <form method="post" action="{{ url('/keluar') }}" style="margin:0">
                            @csrf
                            <button type="submit" style="background:0;border:0;padding:0;color:#fff;font:inherit;font-size:.88rem;cursor:pointer">Keluar</button>
                        </form>
                    @else
                        <a href="{{ url('/daftar') }}">Daftar Subscriber</a>
                        <a href="{{ url('/masuk') }}">Masuk</a>
                    @endauth
                    <a href="{{ url('/privacy-policy') }}">Kebijakan Privasi</a>
                </div>
            </div>
        </div>
        <div class="kecil">&copy; {{ date('Y') }} {{ $namaSitus }}. Seluruh hak cipta dilindungi.</div>
    </div>
</footer>

<script>
    /* Laci sidebar: satu jalur untuk desktop & HP. */
    function bukaMenu() {
        document.body.classList.add('menu-terbuka');
        var t = document.getElementById('tombolMenu');
        var s = document.getElementById('sisiMenu');
        if (t) { t.setAttribute('aria-expanded', 'true'); }
        if (s) { s.setAttribute('aria-hidden', 'false'); }
    }
    function tutupMenu() {
        document.body.classList.remove('menu-terbuka');
        var t = document.getElementById('tombolMenu');
        var s = document.getElementById('sisiMenu');
        if (t) { t.setAttribute('aria-expanded', 'false'); }
        if (s) { s.setAttribute('aria-hidden', 'true'); }
    }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' || e.key === 'Esc') { tutupMenu(); } });
    // Setelah menekan tautan di dalam laci, laci menutup sendiri.
    document.addEventListener('click', function (e) {
        if (!document.body.classList.contains('menu-terbuka')) { return; }
        var a = e.target && e.target.closest ? e.target.closest('.sb-sisi a') : null;
        if (a) { setTimeout(tutupMenu, 60); }
    });
    // Pintasan: /#menu membuka sidebar langsung (berguna untuk pratinjau & tautan)
    if (window.location.hash === '#menu') { bukaMenu(); }
</script>
<div class="notif-pesan" id="notifPesan" role="status" aria-live="polite"></div>
<script>
(function () {
    var tombol = document.getElementById('loncengNotif');
    if (!tombol) { return; }
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) { return; }

    var kotakPesan = document.getElementById('notifPesan');
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var daftar = null;

    function pesan(teks) {
        if (!kotakPesan) { return; }
        kotakPesan.textContent = teks;
        kotakPesan.classList.add('tampil');
        setTimeout(function () { kotakPesan.classList.remove('tampil'); }, 3400);
    }

    function tandai(aktif) {
        tombol.hidden = false;
        tombol.classList.toggle('nyala', !!aktif);
        tombol.title = aktif ? 'Notifikasi aktif — tekan untuk mematikan' : 'Nyalakan notifikasi tulisan baru';
        tombol.setAttribute('aria-label', tombol.title);
    }

    function kunciBiner(teks) {
        var s = teks.replace(/-/g, '+').replace(/_/g, '/');
        while (s.length % 4) { s += '='; }
        var biner = atob(s);
        var keluar = new Uint8Array(biner.length);
        for (var i = 0; i < biner.length; i++) { keluar[i] = biner.charCodeAt(i); }
        return keluar;
    }

    function kirimKe(alamat, isi) {
        return fetch(alamat, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: JSON.stringify(isi)
        });
    }

    navigator.serviceWorker.register('/sw.js').catch(function () {});
    navigator.serviceWorker.ready.then(function (reg) {
        daftar = reg;
        return reg.pushManager.getSubscription();
    }).then(function (langganan) {
        tandai(!!langganan);
    }).catch(function () { tombol.hidden = false; });

    tombol.addEventListener('click', function () {
        if (!daftar) { pesan('Peramban ini belum mendukung notifikasi.'); return; }
        tombol.disabled = true;

        daftar.pushManager.getSubscription().then(function (langganan) {
            if (langganan) {
                var titik = langganan.endpoint;
                return langganan.unsubscribe().then(function () {
                    return kirimKe('/api/push/hapus', { endpoint: titik });
                }).then(function () {
                    tandai(false);
                    pesan('Notifikasi dimatikan.');
                });
            }

            if (Notification.permission === 'denied') {
                pesan('Izin notifikasi diblokir. Buka pengaturan peramban untuk mengizinkan.');
                return null;
            }

            return Notification.requestPermission().then(function (izin) {
                if (izin !== 'granted') {
                    pesan('Izin notifikasi tidak diberikan.');
                    return null;
                }

                return fetch('/api/push/vapid').then(function (r) { return r.json(); }).then(function (data) {
                    return daftar.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: kunciBiner(data.publicKey) });
                }).then(function (baru) {
                    return kirimKe('/api/push/simpan', {
                        endpoint: baru.endpoint,
                        keys: baru.toJSON().keys,
                        device: (navigator.userAgent || '').slice(0, 90)
                    });
                }).then(function () {
                    tandai(true);
                    pesan('Mantap — notifikasi tulisan baru sudah aktif.');
                });
            });
        }).catch(function () {
            pesan('Gagal mengaktifkan notifikasi. Coba lagi sebentar.');
        }).then(function () { tombol.disabled = false; });
    });
})();
</script>
@stack('skrip')
</body>
</html>
