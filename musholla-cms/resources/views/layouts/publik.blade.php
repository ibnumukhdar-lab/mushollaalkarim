@php
    $namaSitus = $pengaturan['nama_situs'] ?? 'Musholla Al Karim';
    $slogan = $pengaturan['slogan'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', $namaSitus)</title>
    <meta name="description" content="@yield('deskripsi', $slogan ?: 'Musholla Al Karim — kajian, pendidikan Al-Qur\'an, dan kegiatan umat.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('judul', $namaSitus)">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <style>
        :root {
            --navy: #1f3a5f;
            --navy-lembut: #2c4f7c;
            --tinta: #22303f;
            --tinta-muda: #5b6b7c;
            --kertas: #f6f7f9;
            --kartu: #ffffff;
            --garis: #e3e7ec;
            --emas: #c8a24a;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--tinta); background: var(--kertas); line-height: 1.7;
            -webkit-text-size-adjust: 100%;
        }
        a { color: var(--navy); text-decoration: none; }
        a:hover { text-decoration: underline; }
        img { max-width: 100%; height: auto; border-radius: 12px; }
        .wadah { width: 100%; max-width: 1080px; margin: 0 auto; padding: 0 1.1rem; }

        /* kepala */
        header.situs { background: var(--navy); color: #fff; }
        .kepala-baris { display: flex; align-items: center; gap: .8rem; padding: .85rem 0; }
        .merek { display: flex; flex-direction: column; line-height: 1.15; margin-right: auto; }
        .merek strong { font-size: 1.02rem; letter-spacing: .2px; }
        .merek span { font-size: .72rem; opacity: .78; }
        .tombol-menu {
            background: rgba(255,255,255,.12); border: 0; color: #fff; font-size: 1.1rem;
            width: 38px; height: 38px; border-radius: 10px; cursor: pointer; line-height: 1;
        }
        nav.situs { display: none; flex-wrap: wrap; gap: .35rem; }
        nav.situs.buka { display: flex; padding-bottom: .8rem; }
        nav.situs a {
            color: #e8eefa; font-size: .85rem; padding: .4rem .7rem; border-radius: 9px;
            background: rgba(255,255,255,.07);
        }
        nav.situs a:hover, nav.situs a.aktif { background: rgba(255,255,255,.2); text-decoration: none; }
        @media (min-width: 820px) {
            .tombol-menu { display: none; }
            nav.situs { display: flex !important; padding-bottom: 0; }
            .merek { flex-direction: row; align-items: baseline; gap: .6rem; }
        }

        main { padding: 1.6rem 0 3rem; }
        .pahlawan {
            background: linear-gradient(140deg, var(--navy) 0%, var(--navy-lembut) 100%);
            color: #fff; border-radius: 18px; padding: 1.9rem 1.4rem; margin-bottom: 1.6rem;
        }
        .pahlawan h1 { margin: 0 0 .5rem; font-size: 1.55rem; line-height: 1.3; }
        .pahlawan p { margin: 0 0 1.1rem; opacity: .9; font-size: .95rem; }
        .aksi { display: flex; flex-wrap: wrap; gap: .6rem; }
        .tombol {
            display: inline-block; padding: .55rem 1rem; border-radius: 10px; font-size: .88rem;
            font-weight: 600; background: #fff; color: var(--navy);
        }
        .tombol.garis { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.5); }
        .tombol:hover { text-decoration: none; opacity: .92; }

        h2.bagian { font-size: 1.12rem; margin: 2rem 0 .9rem; color: var(--navy); }
        .kartu {
            background: var(--kartu); border: 1px solid var(--garis); border-radius: 14px;
            padding: 1.15rem 1.2rem; box-shadow: 0 1px 2px rgba(31,58,95,.04);
        }
        .jaring { display: grid; gap: 1rem; }
        @media (min-width: 700px) { .jaring.dua { grid-template-columns: 1fr 1fr; } .jaring.tiga { grid-template-columns: repeat(3, 1fr); } }
        .kartu h3 { margin: .1rem 0 .35rem; font-size: 1rem; color: var(--navy); }
        .kartu .tanggal { font-size: .76rem; color: var(--tinta-muda); text-transform: uppercase; letter-spacing: .4px; }
        .kartu p { margin: .4rem 0 0; font-size: .92rem; color: var(--tinta-muda); }

        .isi-halaman :first-child { margin-top: 0; }
        .isi-halaman h1, .isi-halaman h2, .isi-halaman h3 { color: var(--navy); line-height: 1.35; }
        .isi-halaman h2 { font-size: 1.15rem; margin: 1.6rem 0 .6rem; }
        .isi-halaman h3 { font-size: 1.02rem; margin: 1.3rem 0 .5rem; }
        .isi-halaman p { margin: .7rem 0; }
        .isi-halaman img { margin: .6rem 0; }
        .isi-halaman ul, .isi-halaman ol { padding-left: 1.2rem; }
        .isi-halaman table { width: 100%; border-collapse: collapse; font-size: .9rem; display: block; overflow-x: auto; }
        .isi-halaman th, .isi-halaman td { border: 1px solid var(--garis); padding: .5rem .6rem; text-align: left; }

        .remah { font-size: .8rem; color: var(--tinta-muda); margin-bottom: .7rem; }
        .judul-halaman { font-size: 1.4rem; color: var(--navy); margin: .2rem 0 1.1rem; }

        footer.situs { background: var(--navy); color: #d6e0f0; padding: 1.8rem 0 2.2rem; font-size: .86rem; }
        footer.situs a { color: #fff; }
        footer.situs .tautan { display: flex; flex-wrap: wrap; gap: .5rem 1rem; margin-bottom: 1rem; }
        footer.situs .kecil { opacity: .72; font-size: .78rem; }
        /* formulir */
        .form-kartu .baris { margin-bottom: .9rem; }
        .form-kartu label { display: block; font-size: .82rem; font-weight: 600; color: var(--navy); margin-bottom: .3rem; }
        .form-kartu .wajib { color: #a4373f; }
        .form-kartu input, .form-kartu select, .form-kartu textarea {
            width: 100%; font: inherit; font-size: .92rem; padding: .55rem .7rem;
            border: 1px solid var(--garis); border-radius: 10px; background: #fff; color: var(--tinta);
        }
        .form-kartu input[type=file] { padding: .45rem; background: #fbfcfd; }
        .form-kartu input:focus, .form-kartu select:focus, .form-kartu textarea:focus {
            outline: 2px solid rgba(31,58,95,.18); border-color: var(--navy);
        }
        .form-kartu small { display: block; font-size: .74rem; color: var(--tinta-muda); margin-top: .25rem; }
        .tombol-kirim {
            font: inherit; font-weight: 600; font-size: .92rem; padding: .65rem 1.3rem; border: 0;
            border-radius: 10px; background: var(--navy); color: #fff; cursor: pointer;
        }
        .tombol-kirim:hover { background: var(--navy-lembut); }
        .pesan-sukses {
            background: #e8f5ec; border: 1px solid #bfe3cb; color: #1f5a37;
            padding: .9rem 1.1rem; border-radius: 12px; margin-bottom: 1.2rem; font-size: .9rem;
        }
        .pesan-galat {
            background: #fdeced; border: 1px solid #f2c3c6; color: #8c2b33;
            padding: .9rem 1.1rem; border-radius: 12px; margin-bottom: 1.2rem; font-size: .9rem;
        }
        .kosong { color: var(--tinta-muda); font-style: italic; }
        /* tautan masuk/keluar di kepala & kaki */
        nav.situs a.masuk { background: rgba(255,255,255,.2); font-weight: 600; }
        nav.situs button.keluar {
            font: inherit; font-size: .85rem; color: #e8eefa; padding: .4rem .7rem;
            border: 0; border-radius: 9px; background: rgba(255,255,255,.07); cursor: pointer;
        }
        nav.situs button.keluar:hover { background: rgba(255,255,255,.2); }
    </style>
</head>
<body>
<header class="situs">
    <div class="wadah">
        <div class="kepala-baris">
            <button class="tombol-menu" onclick="document.getElementById('navUtama').classList.toggle('buka')" aria-label="Buka menu">☰</button>
            <a href="/" class="merek" style="color:#fff">
                <strong>{{ $namaSitus }}</strong>
                @if ($slogan) <span>{{ $slogan }}</span> @endif
            </a>
        </div>
        <nav class="situs" id="navUtama">
            <a href="/">Beranda</a>
            @foreach ($menu as $m)
                <a href="/{{ $m['slug'] }}" @class(['aktif' => request()->is($m['slug'])])>{{ $m['judul'] }}</a>
            @endforeach
            <a href="/berita">Berita</a>
            @auth
                <a href="/anggota" @class(['aktif' => request()->is('anggota')])>Halo, {{ \Illuminate\Support\Str::limit(auth()->user()->nama_lengkap ?: auth()->user()->name, 14) }}</a>
                <form method="post" action="/keluar" style="display:inline;margin:0">
                    @csrf
                    <button type="submit" class="keluar">Keluar</button>
                </form>
            @else
                <a href="/masuk" @class(['masuk' => true, 'aktif' => request()->is('masuk')])>Masuk</a>
                <a href="/daftar" @class(['aktif' => request()->is('daftar')])>Daftar</a>
            @endauth
        </nav>
    </div>
</header>

<main>
    <div class="wadah">
        @yield('isi')
    </div>
</main>

<footer class="situs">
    <div class="wadah">
        <div class="tautan">
            <a href="/">Beranda</a>
            @foreach ($menu as $m)
                <a href="/{{ $m['slug'] }}">{{ $m['judul'] }}</a>
            @endforeach
            <a href="/berita">Berita</a>
            @auth
                <a href="/anggota">Akun Saya</a>
            @else
                <a href="/daftar">Daftar Subscriber</a>
                <a href="/masuk">Masuk</a>
            @endauth
        </div>
        <div class="kecil">
            &copy; {{ date('Y') }} {{ $namaSitus }}. Seluruh hak cipta dilindungi.
        </div>
    </div>
</footer>
</body>
</html>
