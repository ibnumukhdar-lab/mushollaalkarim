@php
    $namaSitus = $pengaturan['nama_situs'] ?? 'Musholla Al Karim';
    $u = auth()->user();
    $namaPengurus = $u?->nama_lengkap ?: $u?->name;
    $grupMenu = $grup ?? \App\Support\Panel::menuSamping();
    $modulAktif = request()->route('modul');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('judul', 'Panel') — {{ $namaSitus }}</title>
    <style>
        :root {
            /* Warna khas Musholla Al Karim — hijau soft */
            --hijau: #3f7d5c;
            --hijau-tua: #2f6046;
            --hijau-gelap: #24503a;
            --hijau-lembut: #6aa383;
            --hijau-muda: #e8f2ec;
            --hijau-garis: #d8e7de;
            --emas: #c9a961;
            --merah: #b1484f;
            --kuning: #a8811f;
            --tinta: #22302a;
            --tinta-muda: #64766c;
            --kertas: #f3f7f4;
            --kartu: #ffffff;
            --garis: #e2ebe5;
            --radius: 14px;
            --lebar-sisi: 252px;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--kertas); color: var(--tinta); line-height: 1.6; font-size: 15px;
            -webkit-text-size-adjust: 100%;
        }
        body.menu-terbuka { overflow: hidden; }
        a { color: var(--hijau-tua); text-decoration: none; }
        a:hover { text-decoration: none; }
        svg { width: 1.05em; height: 1.05em; flex: none; }

        /* ============ sisi kiri ============ */
        .sisi {
            position: fixed; top: 0; left: 0; bottom: 0; width: var(--lebar-sisi);
            background: linear-gradient(180deg, #2f6046 0%, #24503a 100%);
            color: #e7f1ea; display: flex; flex-direction: column; z-index: 60;
            transform: translateX(-102%); transition: transform .26s ease;
            box-shadow: 4px 0 22px rgba(24, 52, 38, .18);
        }
        body.menu-terbuka .sisi { transform: translateX(0); }
        .sisi-merek {
            display: flex; align-items: center; gap: .55rem; padding: .8rem .95rem .75rem;
            border-bottom: 1px solid rgba(255,255,255,.12);
        }
        .sisi-merek .lambang {
            width: 38px; height: 38px; border-radius: 12px; background: rgba(255,255,255,.14);
            display: inline-flex; align-items: center; justify-content: center; color: #fff;
        }
        .sisi-merek .lambang svg { width: 22px; height: 22px; }
        .sisi-merek .teks { display: flex; flex-direction: column; line-height: 1.2; min-width: 0; }
        .sisi-merek .teks strong { color: #fff; font-size: .95rem; line-height: 1.25; overflow-wrap: anywhere; }
        .sisi-merek .teks span { font-size: .7rem; color: #a9c9b6; letter-spacing: .5px; text-transform: uppercase; }
        .sisi-tutup {
            margin-left: auto; background: rgba(255,255,255,.12); border: 0; color: #fff;
            width: 32px; height: 32px; border-radius: 10px; cursor: pointer; font-size: 1rem; line-height: 1;
        }
        .sisi-nav { flex: 1; overflow-y: auto; padding: .35rem .6rem .7rem; }
        .sisi-grup { margin-bottom: .35rem; }
        .sisi-grup > h6 {
            margin: .25rem .7rem .2rem; font-size: .66rem; letter-spacing: 1px; text-transform: uppercase;
            color: #9dc0ac; font-weight: 600;
        }
        .sisi-nav a {
            display: flex; align-items: center; gap: .55rem; padding: .35rem .7rem; border-radius: 10px;
            color: #dcebe2; font-size: .865rem; margin-bottom: .03rem;
        }
        .sisi-nav a:hover { background: rgba(255,255,255,.09); color: #fff; }
        .sisi-nav a.aktif { background: rgba(255,255,255,.16); color: #fff; font-weight: 600; box-shadow: inset 3px 0 0 var(--emas); }
        .sisi-nav a svg { width: 17px; height: 17px; opacity: .9; }
        .sisi-kaki { border-top: 1px solid rgba(255,255,255,.12); padding: .6rem .9rem .75rem; }
        .sisi-kaki .nama { color: #fff; font-size: .86rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sisi-kaki .peran { font-size: .7rem; color: #9dc0ac; margin-bottom: .45rem; overflow-wrap: anywhere; }
        .sisi-kaki a, .sisi-kaki button {
            display: flex; align-items: center; gap: .5rem; width: 100%; background: rgba(255,255,255,.1);
            border: 0; color: #eaf4ee; font: inherit; font-size: .84rem; padding: .38rem .7rem;
            border-radius: 10px; cursor: pointer; margin-bottom: .28rem; text-align: left;
        }
        .sisi-kaki a:hover, .sisi-kaki button:hover { background: rgba(255,255,255,.18); }
        .selubung {
            position: fixed; inset: 0; background: rgba(28, 44, 36, .45); z-index: 55;
            opacity: 0; visibility: hidden; transition: opacity .22s ease;
        }
        body.menu-terbuka .selubung { opacity: 1; visibility: visible; }

        /* ============ utama ============ */
        .utama { min-height: 100vh; display: flex; flex-direction: column; }
        header.atas {
            position: sticky; top: 0; z-index: 40; background: #fff; border-bottom: 1px solid var(--garis);
            display: flex; align-items: center; gap: .7rem; padding: .55rem 1rem; min-height: 64px;
        }
        .tombol-menu {
            border: 1px solid var(--hijau-garis); background: var(--hijau-muda); color: var(--hijau-tua);
            width: 38px; height: 38px; border-radius: 11px; cursor: pointer; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .tombol-menu:hover { background: #dcece3; }
        .kepala-judul { min-width: 0; margin-right: auto; }
        .kepala-judul .remah { font-size: .74rem; color: var(--tinta-muda); }
        .kepala-judul h1 { margin: 0; font-size: 1.06rem; color: var(--hijau-tua); line-height: 1.3; }
        .kepala-aksi { display: flex; align-items: center; gap: .45rem; }
        .cip-akun {
            display: inline-flex; align-items: center; gap: .5rem; padding: .3rem .6rem .3rem .35rem;
            border: 1px solid var(--garis); border-radius: 11px; background: #fff; color: var(--tinta);
        }
        .cip-akun .bulat {
            width: 28px; height: 28px; border-radius: 9px; background: var(--hijau-muda); color: var(--hijau-tua);
            display: inline-flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 700;
        }
        .cip-akun .nama-kecil { font-size: .84rem; }

        main.isi { padding: 1.25rem 1.1rem 3rem; }
        .wadah-panel { max-width: 1120px; margin: 0 auto; }

        /* ============ kartu & tabel ============ */
        .kartu {
            background: var(--kartu); border: 1px solid var(--garis); border-radius: var(--radius);
            box-shadow: 0 1px 2px rgba(36, 80, 58, .05); padding: 1.1rem 1.15rem;
        }
        .kartu-kepala { display: flex; align-items: center; gap: .6rem; margin-bottom: .9rem; flex-wrap: wrap; }
        .kartu-kepala h3 { margin: 0; font-size: .98rem; color: var(--hijau-tua); margin-right: auto; }
        .kartu h3 { color: var(--hijau-tua); }

        .stat-baris { display: grid; gap: .9rem; grid-template-columns: 1fr; margin-bottom: 1.1rem; }
        @media (min-width: 640px) { .stat-baris { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1000px) { .stat-baris { grid-template-columns: repeat(4, 1fr); } }
        .stat {
            background: #fff; border: 1px solid var(--garis); border-radius: var(--radius); padding: 1rem 1.05rem;
            display: flex; align-items: center; gap: .85rem;
        }
        .stat .ling { width: 42px; height: 42px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; flex: none; }
        .stat .ling svg { width: 21px; height: 21px; }
        .stat .label { display: block; font-size: .74rem; text-transform: uppercase; letter-spacing: .6px; color: var(--tinta-muda); }
        .stat .angka { display: block; font-size: 1.18rem; font-weight: 700; color: var(--hijau-tua); line-height: 1.3; }
        .stat .kecil { display: block; font-size: .74rem; color: var(--tinta-muda); }
        .ling-hijau { background: var(--hijau-muda); color: var(--hijau); }
        .ling-emas { background: #f7f0dd; color: var(--kuning); }
        .ling-merah { background: #fbeced; color: var(--merah); }

        .tabel-bungkus { overflow-x: auto; }
        table.tabel { width: 100%; border-collapse: collapse; font-size: .9rem; }
        table.tabel th {
            text-align: left; font-size: .7rem; text-transform: uppercase; letter-spacing: .7px;
            color: var(--tinta-muda); font-weight: 600; padding: .55rem .6rem; border-bottom: 1px solid var(--garis);
            white-space: nowrap;
        }
        table.tabel td { padding: .6rem; border-bottom: 1px solid #eef4f0; vertical-align: middle; overflow-wrap: break-word; word-break: break-word; min-width: 0; }
        table.tabel tr:last-child td { border-bottom: 0; }
        table.tabel tr:hover td { background: #f8fbf9; }
        td.angka { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .aksi-baris { display: flex; gap: .3rem; justify-content: flex-end; }
        .ikon-tbl {
            width: 32px; height: 32px; border-radius: 9px; border: 1px solid var(--garis); background: #fff;
            color: var(--hijau-tua); cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
        }
        .ikon-tbl:hover { background: var(--hijau-muda); }
        .ikon-tbl.bahaya:hover { background: #fbeced; color: var(--merah); border-color: #f0c8ca; }

        .lencana {
            display: inline-block; font-size: .72rem; font-weight: 600; padding: .18rem .55rem; border-radius: 999px;
            background: var(--hijau-muda); color: var(--hijau-tua); white-space: nowrap;
        }
        .lencana.kuning { background: #f8f1dd; color: var(--kuning); }
        .lencana.merah { background: #fbeced; color: var(--merah); }
        .lencana.abu { background: #eef2f0; color: var(--tinta-muda); }
        .lencana.masuk { background: #e8f4ec; color: #1f6b41; }
        .lencana.keluar { background: #fbeced; color: var(--merah); }

        .tbl {
            display: inline-flex; align-items: center; gap: .4rem; font: inherit; font-size: .86rem; font-weight: 600;
            padding: .48rem .85rem; border-radius: 10px; border: 1px solid transparent; cursor: pointer;
        }
        .tbl-utama { background: var(--hijau); color: #fff; }
        .tbl-utama:hover { background: var(--hijau-tua); }
        .tbl-samar { background: #fff; border-color: var(--garis); color: var(--hijau-tua); }
        .tbl-samar:hover { background: var(--hijau-muda); }

        .cari-baris { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
        .cari-baris input[type=search], .cari-baris input[type=text] {
            font: inherit; font-size: .88rem; padding: .48rem .7rem; border: 1px solid var(--garis);
            border-radius: 10px; min-width: 12rem; flex: 1 1 12rem;
        }

        .pesan-sukses {
            background: #e9f6ee; border: 1px solid #bfe3cb; color: #1f5a37; padding: .8rem 1rem;
            border-radius: 12px; margin-bottom: 1.1rem; font-size: .9rem;
        }
        .pesan-galat {
            background: #fdeced; border: 1px solid #f2c3c6; color: #8c2b33; padding: .8rem 1rem;
            border-radius: 12px; margin-bottom: 1.1rem; font-size: .9rem;
        }
        .pesan-galat ul { margin: .35rem 0 0 1.1rem; }

        /* ============ formulir ============ */
        .bidang { margin-bottom: .95rem; }
        .bidang > label { display: block; font-size: .82rem; font-weight: 600; color: var(--hijau-tua); margin-bottom: .3rem; }
        .bidang .wajib { color: var(--merah); }
        .bidang input[type=text], .bidang input[type=number], .bidang input[type=email], .bidang input[type=date],
        .bidang input[type=datetime-local], .bidang input[type=password], .bidang input[type=file],
        .bidang select, .bidang textarea {
            width: 100%; font: inherit; font-size: .92rem; padding: .55rem .7rem;
            border: 1px solid var(--garis); border-radius: 10px; background: #fff; color: var(--tinta);
        }
        .bidang input:focus, .bidang select:focus, .bidang textarea:focus {
            outline: 2px solid rgba(63, 125, 92, .2); border-color: var(--hijau);
        }
        .bidang textarea { resize: vertical; line-height: 1.6; }
        .bidang .bantuan { display: block; font-size: .74rem; color: var(--tinta-muda); margin-top: .25rem; }
        .bidang.saklar { display: flex; align-items: center; gap: .55rem; }
        .bidang.saklar > label { margin: 0; font-weight: 500; font-size: .88rem; }
        .bidang.saklar input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--hijau); }
        .jaring-2 { display: grid; gap: 0 .9rem; }
        @media (min-width: 760px) { .jaring-2 { grid-template-columns: 1fr 1fr; } }
        .lebar-penuh { grid-column: 1 / -1; }
        .lampiran-lama { display: flex; align-items: center; gap: .6rem; margin-top: .45rem; font-size: .8rem; color: var(--tinta-muda); }
        .lampiran-lama img { max-height: 70px; border-radius: 9px; border: 1px solid var(--garis); }
        .kaki-form { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: 1.2rem; padding-top: 1rem; border-top: 1px solid var(--garis); }

        .halaman { display: flex; justify-content: center; gap: .3rem; margin-top: 1rem; flex-wrap: wrap; }
        .halaman a, .halaman span {
            min-width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid var(--garis); border-radius: 9px; background: #fff; font-size: .84rem; color: var(--tinta-muda);
            padding: 0 .5rem;
        }
        .halaman .aktif { background: var(--hijau); color: #fff; border-color: var(--hijau); font-weight: 600; }
        .kosong { color: var(--tinta-muda); font-style: italic; font-size: .9rem; }
        .pemisah { height: 1px; background: var(--garis); margin: 1.1rem 0; }

        /* mode sempit: tabel jadi kartu */
        @media (max-width: 760px) {
            table.tabel thead { display: none; }
            table.tabel tr { display: block; border: 1px solid var(--garis); border-radius: 12px; padding: .65rem .75rem; margin-bottom: .6rem; background: #fff; }
            table.tabel td {
                display: grid; grid-template-columns: minmax(5.5rem, .8fr) minmax(0, 1.2fr);
                gap: .6rem; align-items: baseline; border: 0; padding: .22rem 0;
                text-align: right; overflow-wrap: anywhere;
            }
            table.tabel td::before {
                content: attr(data-label); font-size: .72rem; color: var(--tinta-muda);
                text-transform: uppercase; letter-spacing: .4px; text-align: left;
            }
            td.angka { text-align: right; white-space: normal; }
            .aksi-baris { justify-content: flex-end; }
        }
        @media (max-width: 560px) {
            .cip-akun .nama-kecil { display: none; }
            .cip-akun { padding: .3rem; }
            .kepala-judul .remah { display: none; }
        }

        @media (min-width: 1024px) {
            .sisi { transform: translateX(0); box-shadow: none; }
            .sisi-tutup { display: none; }
            .utama { margin-left: var(--lebar-sisi); }
            .tombol-menu { display: none; }
            .selubung { display: none; }
            main.isi { padding: 1.4rem 1.6rem 3rem; }
        }
    </style>
    @stack('gaya')
</head>
<body>

<aside class="sisi" id="sisiPanel">
    <div class="sisi-merek">
        <span class="lambang">@include('panel._ikon', ['nama' => 'masjid'])</span>
        <span class="teks">
            <strong>{{ $namaSitus }}</strong>
            <span>Panel Pengelola</span>
        </span>
        <button type="button" class="sisi-tutup" aria-label="Tutup menu" onclick="tutupMenu()">✕</button>
    </div>

    <nav class="sisi-nav">
        <div class="sisi-grup">
            <a href="{{ route('panel.dasbor') }}" @class(['aktif' => request()->routeIs('panel.dasbor')])>
                @include('panel._ikon', ['nama' => 'dasbor']) Dasbor
            </a>
        </div>

        @foreach ($grupMenu as $kunciGrup => $daftarModul)
            <div class="sisi-grup">
                <h6>{{ \App\Support\Panel::GRUP[$kunciGrup]['judul'] }}</h6>
                @foreach ($daftarModul as $m)
                    <a href="{{ route('panel.daftar', $m['kunci']) }}" @class(['aktif' => $modulAktif === $m['kunci']])>
                        @include('panel._ikon', ['nama' => $m['ikon']]) {{ $m['judul'] }}
                    </a>
                @endforeach
                @if ($kunciGrup === 'keuangan')
                    <a href="{{ route('panel.qris') }}" @class(['aktif' => request()->routeIs('panel.qris')])>
                        @include('panel._ikon', ['nama' => 'gear']) Infaq &amp; QRIS
                    </a>
                @endif
            </div>
        @endforeach

    </nav>

    <div class="sisi-kaki">
        <div class="nama">{{ $namaPengurus }}</div>
        <div class="peran">{{ $u?->email }}</div>
        <a href="{{ route('panel.pengaturan') }}" @class(['aktif' => request()->routeIs('panel.pengaturan')])>
            @include('panel._ikon', ['nama' => 'gear']) Pengaturan Situs
        </a>
        <a href="{{ url('/') }}" target="_blank" rel="noopener">
            @include('panel._ikon', ['nama' => 'situs']) Lihat Situs
        </a>
        <a href="{{ url('/anggota') }}">
            @include('panel._ikon', ['nama' => 'akun']) Akun Saya
        </a>
        <form method="post" action="{{ url('/keluar') }}" style="margin:0">
            @csrf
            <button type="submit">@include('panel._ikon', ['nama' => 'keluar']) Keluar</button>
        </form>
    </div>
</aside>

<div class="selubung" onclick="tutupMenu()"></div>

<div class="utama">
    <header class="atas">
        <button type="button" class="tombol-menu" aria-label="Buka menu" aria-expanded="false" onclick="bukaMenu()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <div class="kepala-judul">
            <div class="remah">@yield('remah', 'Panel Pengelola')</div>
            <h1>@yield('judul', 'Dasbor')</h1>
        </div>
        <div class="kepala-aksi">
            @yield('aksi')
            <span class="cip-akun">
                <span class="bulat">{{ strtoupper(substr($namaPengurus ?? 'P', 0, 1)) }}</span>
                <span class="nama-kecil">{{ \Illuminate\Support\Str::limit($namaPengurus, 16) }}</span>
            </span>
        </div>
    </header>

    <main class="isi">
        <div class="wadah-panel">
            @if (session('sukses'))
                <div class="pesan-sukses">{{ session('sukses') }}</div>
            @endif
            @if ($errors->any())
                <div class="pesan-galat">
                    <strong>Ada yang perlu diperbaiki:</strong>
                    <ul>
                        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                    </ul>
                </div>
            @endif
            @yield('isi')
        </div>
    </main>
</div>

<script>
    function bukaMenu() {
        document.body.classList.add('menu-terbuka');
        var t = document.querySelector('.tombol-menu');
        if (t) t.setAttribute('aria-expanded', 'true');
    }
    function tutupMenu() {
        document.body.classList.remove('menu-terbuka');
        var t = document.querySelector('.tombol-menu');
        if (t) t.setAttribute('aria-expanded', 'false');
    }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutupMenu(); });
</script>
@stack('skrip')
</body>
</html>
