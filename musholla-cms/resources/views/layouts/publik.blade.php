@php
    $namaSitus = $pengaturan['nama_situs'] ?? 'Musholla Al Karim';
    $slogan = $pengaturan['slogan'] ?? null;
    $menuSitus = \App\Support\Menu::utama();
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
    <meta name="description" content="@yield('deskripsi', $slogan ?: 'Musholla Al Karim — kajian, kegiatan umat, dan infaq terbuka.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('judul', $namaSitus)">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="theme-color" content="#3f7d5c">
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

        /* ================= kepala ================= */
        header.situs {
            background: #fff; border-bottom: 1px solid var(--hijau-garis);
            position: sticky; top: 0; z-index: 40;
        }
        .kepala { display: flex; align-items: center; gap: .6rem; min-height: 62px; }
        .tombol-menu {
            border: 1px solid var(--hijau-garis); background: var(--hijau-muda); color: var(--hijau-tua);
            width: 38px; height: 38px; border-radius: 11px; cursor: pointer; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .tombol-menu:hover { background: #e2efe7; }
        .tombol-menu svg { width: 20px; height: 20px; }
        .merek { display: flex; align-items: center; gap: .55rem; margin-right: auto; color: var(--hijau-tua); }
        .merek:hover { text-decoration: none; }
        .lambang {
            width: 36px; height: 36px; border-radius: 11px; flex: none;
            background: var(--hijau); color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .lambang svg { width: 21px; height: 21px; }
        .merek-teks { display: flex; flex-direction: column; line-height: 1.2; min-width: 0; }
        .merek-teks strong { font-size: 1rem; letter-spacing: .1px; color: var(--hijau-tua); white-space: nowrap; }
        .merek-teks span {
            display: none; font-size: .7rem; color: var(--tinta-muda);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        nav.atas { display: none; align-items: center; gap: .18rem; }
        nav.atas a {
            font-size: .88rem; color: var(--tinta); padding: .42rem .7rem; border-radius: 10px;
            display: inline-flex; align-items: center; gap: .4rem;
        }
        nav.atas a:hover { background: var(--hijau-muda); text-decoration: none; }
        nav.atas a.aktif { background: var(--hijau-muda); color: var(--hijau-tua); font-weight: 600; }
        nav.atas svg { width: 16px; height: 16px; opacity: .8; }
        .kepala-aksi { display: flex; align-items: center; gap: .45rem; }
        .tombol-akun {
            display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
            border: 1px solid var(--hijau-garis); background: #fff; color: var(--hijau-tua);
            width: 38px; height: 38px; border-radius: 11px; font-size: .85rem;
        }
        .tombol-akun:hover { background: var(--hijau-muda); text-decoration: none; }
        .tombol-daftar {
            display: none; align-items: center; gap: .35rem; background: var(--hijau); color: #fff;
            font-size: .85rem; font-weight: 600; padding: .45rem .9rem; border-radius: 10px;
        }
        .tombol-daftar:hover { background: var(--hijau-tua); text-decoration: none; }
        @media (min-width: 940px) {
            .tombol-menu { display: none; }
            nav.atas { display: flex; }
            .tombol-daftar { display: inline-flex; }
            .merek-teks span { display: block; }
        }
        /* ================= sidebar (tombol garis tiga) ================= */
        .selubung {
            position: fixed; inset: 0; background: rgba(38, 51, 44, .42); backdrop-filter: blur(1.5px);
            opacity: 0; visibility: hidden; transition: opacity .22s ease; z-index: 60;
        }
        .sisi {
            position: fixed; top: 0; left: 0; bottom: 0; width: 84%; max-width: 320px; z-index: 70;
            background: #fff; display: flex; flex-direction: column;
            border-right: 1px solid var(--hijau-garis);
            transform: translateX(-102%); transition: transform .26s ease;
            box-shadow: 6px 0 28px rgba(38, 51, 44, .12);
            overflow-y: auto;
        }
        body.menu-terbuka .selubung { opacity: 1; visibility: visible; }
        body.menu-terbuka .sisi { transform: translateX(0); }
        .sisi-kepala {
            display: flex; align-items: center; gap: .55rem; padding: .9rem 1rem;
            border-bottom: 1px solid var(--hijau-garis);
        }
        .sisi-kepala .merek-teks { margin-right: auto; }
        .sisi-tutup {
            border: 1px solid var(--hijau-garis); background: #fff; color: var(--tinta-muda);
            width: 34px; height: 34px; border-radius: 10px; cursor: pointer; font-size: 1.05rem; line-height: 1;
        }
        .sisi-tutup:hover { background: var(--hijau-muda); }
        .sisi-menu { padding: .7rem .6rem; display: flex; flex-direction: column; gap: .18rem; }
        .sisi-menu a {
            display: flex; align-items: center; gap: .6rem; padding: .62rem .7rem; border-radius: 11px;
            color: var(--tinta); font-size: .93rem;
        }
        .sisi-menu a:hover { background: var(--hijau-muda); text-decoration: none; }
        .sisi-menu a.aktif { background: var(--hijau-muda); color: var(--hijau-tua); font-weight: 600; }
        .sisi-menu a svg { color: var(--hijau); }
        .sisi-kaki { margin-top: auto; padding: .9rem 1rem 1.4rem; border-top: 1px solid var(--hijau-garis); }
        .sisi-kaki .nama { font-size: .9rem; font-weight: 600; color: var(--hijau-tua); }
        .sisi-kaki .surel { font-size: .78rem; color: var(--tinta-muda); margin-bottom: .7rem; word-break: break-all; }
        .sisi-kaki .tombol-penuh {
            display: block; text-align: center; font-size: .9rem; font-weight: 600; padding: .6rem 1rem;
            border-radius: 11px; background: var(--hijau); color: #fff; margin-bottom: .5rem;
        }
        .sisi-kaki .tombol-penuh:hover { background: var(--hijau-tua); text-decoration: none; }
        .sisi-kaki .tombol-samar {
            display: block; text-align: center; font-size: .9rem; font-weight: 600; padding: .55rem 1rem;
            border-radius: 11px; border: 1px solid var(--hijau-garis); color: var(--hijau-tua); background: #fff;
            width: 100%; cursor: pointer; font-family: inherit;
        }
        .sisi-kaki .tombol-samar:hover { background: var(--hijau-muda); }
        .sisi-kaki form { margin: 0; }

        /* ================= isi ================= */
        main { padding: 1.5rem 0 3rem; }
        .pahlawan {
            background: linear-gradient(140deg, var(--hijau) 0%, var(--hijau-lembut) 100%);
            color: #fff; border-radius: 18px; padding: 1.9rem 1.4rem; margin-bottom: 1.6rem;
        }
        .pahlawan h1 { margin: 0 0 .5rem; font-size: 1.5rem; line-height: 1.3; }
        .pahlawan p { margin: 0 0 1.1rem; opacity: .94; font-size: .95rem; }
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
    </style>
    @stack('gaya')
</head>
<body>

<script>document.documentElement.classList.add('js');</script>

<header class="situs">
    <div class="wadah kepala">
        <button type="button" class="tombol-menu" id="tombolMenu" aria-label="Buka menu" aria-expanded="false" aria-controls="sisiMenu" onclick="bukaMenu()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>

        <a href="/" class="merek">
            <span class="lambang">@include('publik._ikon', ['nama' => 'masjid'])</span>
            <span class="merek-teks">
                <strong>{{ $namaSitus }}</strong>
                @if ($slogan) <span>{{ \Illuminate\Support\Str::limit($slogan, 46) }}</span> @endif
            </span>
        </a>

        <nav class="atas" aria-label="Menu utama">
            @foreach ($menuSitus as $m)
                <a href="{{ $m['url'] }}" @class(['aktif' => request()->is($m['aktif'])])>
                    @include('publik._ikon', ['nama' => $m['ikon']])
                    {{ $m['judul'] }}
                </a>
            @endforeach
        </nav>

        <div class="kepala-aksi">
            @auth
                <a href="{{ url('/anggota') }}" class="tombol-akun" title="Akun saya" aria-label="Akun saya">
                    @include('publik._ikon', ['nama' => 'akun'])
                </a>
            @else
                <a href="{{ url('/masuk') }}" class="tombol-akun" title="Masuk" aria-label="Masuk">
                    @include('publik._ikon', ['nama' => 'akun'])
                </a>
                <a href="{{ url('/daftar') }}" class="tombol-daftar">Daftar</a>
            @endauth
        </div>
    </div>
</header>

{{-- Sidebar untuk tombol garis tiga (HP & tablet) --}}
<div class="selubung" id="selubungMenu" onclick="tutupMenu()"></div>
<aside class="sisi" id="sisiMenu" aria-label="Menu samping" aria-hidden="true">
    <div class="sisi-kepala">
        <span class="lambang">@include('publik._ikon', ['nama' => 'masjid'])</span>
        <span class="merek-teks">
            <strong>{{ $namaSitus }}</strong>
            <span>Menu</span>
        </span>
        <button type="button" class="sisi-tutup" aria-label="Tutup menu" onclick="tutupMenu()">✕</button>
    </div>

    <nav class="sisi-menu">
        @foreach ($menuSitus as $m)
            <a href="{{ $m['url'] }}" @class(['aktif' => request()->is($m['aktif'])])>
                @include('publik._ikon', ['nama' => $m['ikon']])
                {{ $m['judul'] }}
            </a>
        @endforeach
    </nav>

    <div class="sisi-kaki">
        @auth
            <div class="nama">{{ $namaPengguna }}</div>
            <div class="surel">{{ $pengguna->email }}</div>
            <a href="{{ url('/anggota') }}" class="tombol-penuh">Akun Saya</a>
            <form method="post" action="{{ url('/keluar') }}">
                @csrf
                <button type="submit" class="tombol-samar">Keluar</button>
            </form>
        @else
            <a href="{{ url('/daftar') }}" class="tombol-penuh">Daftar Subscriber</a>
            <a href="{{ url('/masuk') }}" class="tombol-samar">Masuk</a>
        @endauth
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
            </div>
            <div>
                <h4>Menu</h4>
                <div class="tautan">
                    @foreach ($menuSitus as $m)
                        <a href="{{ $m['url'] }}">{{ $m['judul'] }}</a>
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
    function bukaMenu() {
        document.body.classList.add('menu-terbuka');
        var t = document.getElementById('tombolMenu');
        var s = document.getElementById('sisiMenu');
        if (t) t.setAttribute('aria-expanded', 'true');
        if (s) s.setAttribute('aria-hidden', 'false');
    }
    function tutupMenu() {
        document.body.classList.remove('menu-terbuka');
        var t = document.getElementById('tombolMenu');
        var s = document.getElementById('sisiMenu');
        if (t) t.setAttribute('aria-expanded', 'false');
        if (s) s.setAttribute('aria-hidden', 'true');
    }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutupMenu(); });
    // Pintasan: /#menu membuka sidebar langsung (berguna untuk pratinjau & tautan)
    if (window.location.hash === '#menu') { bukaMenu(); }
</script>
@stack('skrip')
</body>
</html>
