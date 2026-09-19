@extends('layouts.publik')

@section('judul', 'Laporan Keuangan — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', 'Transparansi kas Musholla Al Karim: pemasukan, pengeluaran, saldo bulanan, dan catatan tiap transaksi.')

@php
    $rupiah = fn ($angka) => 'Rp ' . number_format((float) $angka, 0, ',', '.');
    $persenPakai = $masukSemua > 0 ? (int) min(100, round($keluarSemua / $masukSemua * 100)) : 0;
    $adaSaringan = $bulan || $jenis || $kategori || $cari !== '';

    // batang rekap bulanan mengikuti bulan terbesar yang sedang ditampilkan
    $tertinggi = 0;
    foreach ($rekapBulanan as $r) {
        if (($r['jenis'] ?? '') === 'bulan') {
            $tertinggi = max($tertinggi, (float) $r['masuk'], (float) $r['keluar']);
        }
    }

    // catatan transaksi dikelompokkan per bulan (gaya buku kas)
    $kelompok = [];
    foreach ($transaksi as $t) {
        $kunci = $t->tanggal ? $t->tanggal->format('Y-m') : 'tanpa-tanggal';
        $kelompok[$kunci][] = $t;
    }
@endphp

@section('isi')
<div class="lapkas">
    <style>
        .lapkas { padding-bottom: 1rem; }
        .lapkas h1 { font-size: clamp(1.35rem, 4vw, 1.75rem); margin: .2rem 0 .35rem; }
        .lapkas .pengantar { color: var(--tinta-muda); font-size: .95rem; max-width: 62ch; margin: 0 0 .8rem; }
        .lapkas .cap-waktu {
            display: inline-flex; align-items: center; gap: .45rem; background: var(--hijau-muda);
            border: 1px solid var(--hijau-garis); color: var(--hijau-tua); border-radius: 999px;
            padding: .32rem .8rem; font-size: .78rem; margin-bottom: 1.2rem;
        }

        /* ===== kartu ringkasan ===== */
        .lapkas .ringkas { display: grid; gap: .85rem; margin-bottom: 1rem; }
        @media (min-width: 820px) { .lapkas .ringkas { grid-template-columns: 1.4fr 1fr 1fr; } }
        .lapkas .kotak { background: var(--kartu); border: 1px solid var(--garis); border-radius: 16px; padding: 1.05rem 1.15rem; }
        .lapkas .kotak.saldo { background: linear-gradient(160deg, #356a4e, #2b5740); border: 0; color: #fff; position: relative; overflow: hidden; }
        .lapkas .kotak.saldo::after { content: ""; position: absolute; right: -46px; top: -46px; width: 170px; height: 170px; border-radius: 50%; background: rgba(255,255,255,.07); }
        .lapkas .kotak .lbl { display: block; font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; color: var(--tinta-muda); }
        .lapkas .kotak.saldo .lbl { color: rgba(255,255,255,.88); }
        .lapkas .kotak .angka { display: block; font-size: clamp(1.5rem, 5vw, 1.95rem); font-weight: 700; letter-spacing: -.4px; font-variant-numeric: tabular-nums; color: var(--hijau-tua); margin-top: .15rem; }
        .lapkas .kotak.saldo .angka { color: #fff; }
        .lapkas .kotak .kecil { display: block; font-size: .78rem; color: var(--tinta-muda); margin-top: .35rem; }
        .lapkas .kotak.saldo .kecil { color: rgba(255,255,255,.88); }
        .lapkas .kotak.masuk .angka { color: #256b46; }
        .lapkas .kotak.keluar .angka { color: #9c3b43; }
        .lapkas .kotak .ikon { width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center; background: var(--hijau-muda); color: var(--hijau-tua); margin-bottom: .5rem; }
        .lapkas .kotak.keluar .ikon { background: #fbeced; color: #9c3b43; }
        .lapkas .kotak .ikon svg { width: 17px; height: 17px; }

        /* bar porsi pemakaian */
        .lapkas .porsi { background: var(--kartu); border: 1px solid var(--garis); border-radius: 16px; padding: .95rem 1.15rem; margin-bottom: 1.5rem; }
        .lapkas .porsi .baris { display: flex; flex-wrap: wrap; gap: .3rem 1rem; justify-content: space-between; font-size: .87rem; color: var(--tinta-muda); }
        .lapkas .porsi .baris strong { color: var(--tinta); }
        .lapkas .bar-porsi { height: 9px; border-radius: 999px; background: var(--hijau-muda); overflow: hidden; display: flex; margin: .55rem 0 .45rem; }
        .lapkas .bar-porsi i { display: block; height: 100%; }
        .lapkas .bar-porsi i.pakai { background: linear-gradient(90deg, #d08a90, #b1484f); }
        .lapkas .bar-porsi i.sisa { background: linear-gradient(90deg, var(--hijau-lembut), var(--hijau)); }

        /* ===== saringan ===== */
        .lapkas .saring { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin-bottom: 1.7rem; }
        .lapkas .saring label { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
        .lapkas .saring select, .lapkas .saring input {
            font: inherit; font-size: .87rem; color: var(--tinta); background: #fff;
            border: 1px solid var(--garis); border-radius: 999px; padding: .45rem .85rem; flex: 1 1 8rem; min-width: 0;
        }
        .lapkas .saring input[type=search] { flex: 1 1 12rem; }
        .lapkas .saring button {
            font: inherit; font-size: .87rem; font-weight: 600; background: var(--hijau); color: #fff;
            border: 0; border-radius: 999px; padding: .48rem 1.05rem; cursor: pointer;
        }
        .lapkas .saring button:hover { background: var(--hijau-tua); }
        .lapkas .saring a { font-size: .82rem; color: var(--tinta-muda); text-decoration: underline; }

        /* ===== judul bagian ===== */
        .lapkas h2.bagian {
            display: flex; align-items: center; gap: .5rem; font-size: 1.08rem; margin: 1.7rem 0 .35rem;
        }
        .lapkas h2.bagian::before { content: ""; width: 4px; height: 1em; border-radius: 3px; background: var(--hijau-lembut); }
        .lapkas .sub { font-size: .85rem; color: var(--tinta-muda); margin: 0 0 1rem; }

        /* ===== rekap bulanan ===== */
        .lapkas .rekap { background: var(--kartu); border: 1px solid var(--garis); border-radius: 16px; padding: .3rem 1rem; }
        .lapkas .bulan { display: grid; grid-template-columns: 1fr; gap: .5rem; padding: .8rem 0; border-bottom: 1px dashed var(--garis); }
        .lapkas .bulan:last-child { border-bottom: 0; }
        /* HP: nama bulan di kiri, angka di kanan — lebih ringkas dibaca */
        @media (max-width: 739px) {
            .lapkas .bulan {
                grid-template-columns: 1fr auto;
                grid-template-areas: "nama angka" "dua saldo";
                column-gap: .8rem; row-gap: .5rem; align-items: start;
            }
            .lapkas .bulan .nama { grid-area: nama; }
            .lapkas .bulan .dua { grid-area: dua; }
            .lapkas .bulan .angka { grid-area: angka; }
            .lapkas .bulan .saldo { grid-area: saldo; }
        }
        @media (min-width: 740px) {
            .lapkas .bulan { grid-template-columns: 8.5rem 1fr 9rem 7rem; gap: .9rem; align-items: center; }
        }
        .lapkas .bulan .nama { font-weight: 600; font-size: .92rem; color: var(--hijau-tua); }
        .lapkas .bulan .nama small { display: block; font-weight: 400; font-size: .72rem; color: var(--tinta-muda); }
        .lapkas .bulan .dua { display: grid; gap: .3rem; }
        .lapkas .baris-bar { display: grid; grid-template-columns: 3.6rem 1fr; gap: .5rem; align-items: center; }
        .lapkas .baris-bar span { font-size: .7rem; color: var(--tinta-muda); }
        .lapkas .baris-bar i { display: block; height: 8px; border-radius: 999px; min-width: 3px; }
        .lapkas .baris-bar i.masuk { background: linear-gradient(90deg, var(--hijau), #4e9470); }
        .lapkas .baris-bar i.keluar { background: linear-gradient(90deg, #cfa2a6, #b1484f); }
        .lapkas .bulan .angka { font-variant-numeric: tabular-nums; font-size: .88rem; text-align: right; }
        .lapkas .bulan .angka em { display: block; font-style: normal; font-size: .78rem; color: var(--tinta-muda); }
        .lapkas .bulan .angka .naik { color: #256b46; font-weight: 600; }
        .lapkas .bulan .saldo { font-variant-numeric: tabular-nums; font-size: .8rem; color: var(--tinta-muda); text-align: right; }
        .lapkas .bulan .saldo b { display: block; color: var(--tinta); font-size: .92rem; }
        .lapkas .bulan.kosong { display: block; color: var(--tinta-muda); font-size: .82rem; padding: .7rem 0; }
        .lapkas .bulan.datar .nama, .lapkas .bulan.datar .angka, .lapkas .bulan.datar .saldo { color: var(--tinta-muda); font-weight: 400; }
        .lapkas .cip-tutup {
            display: inline-block; margin-top: .3rem; font-size: .68rem; letter-spacing: .04em;
            border: 1px solid var(--hijau-garis); background: var(--hijau-muda); color: var(--hijau-tua);
            border-radius: 999px; padding: .05rem .45rem;
        }
        .lapkas .bulan a.nama { text-decoration: none; }
        .lapkas .bulan a.nama:hover { text-decoration: underline; }

        /* ===== buku catatan ===== */
        .lapkas .buku { background: var(--kartu); border: 1px solid var(--garis); border-radius: 16px; overflow: hidden; }
        .lapkas .kepala-bulan {
            background: var(--hijau-muda); border-bottom: 1px solid var(--hijau-garis); padding: .55rem 1rem;
            display: flex; justify-content: space-between; gap: 1rem;
            font-size: .76rem; letter-spacing: .06em; text-transform: uppercase; color: var(--hijau-tua);
        }
        .lapkas .catatan { display: grid; grid-template-columns: 3.4rem 1fr auto; gap: .85rem; align-items: baseline; padding: .78rem 1rem; border-bottom: 1px dashed var(--garis); }
        .lapkas .catatan:last-child { border-bottom: 0; }
        .lapkas .catatan:hover { background: #fbfdfc; }
        .lapkas .catatan .tgl { font-size: .78rem; color: var(--tinta-muda); font-variant-numeric: tabular-nums; }
        .lapkas .catatan .ket { min-width: 0; }
        .lapkas .catatan .ket b { font-weight: 600; font-size: .95rem; overflow-wrap: anywhere; }
        .lapkas .catatan .ket span { display: block; font-size: .78rem; color: var(--tinta-muda); margin-top: .1rem; }
        .lapkas .catatan .nilai { font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; font-size: .96rem; }
        .lapkas .catatan .nilai.masuk { color: #256b46; }
        .lapkas .catatan .nilai.keluar { color: #9c3b43; }
        .lapkas .kaki-buku { display: flex; flex-wrap: wrap; gap: .5rem 1.2rem; justify-content: space-between; padding: .8rem 1rem; border-top: 1px solid var(--garis); font-size: .84rem; color: var(--tinta-muda); }

        .lapkas .balik { display: flex; justify-content: center; align-items: center; gap: .5rem; margin-top: 1rem; font-size: .85rem; color: var(--tinta-muda); }
        .lapkas .balik a { border: 1px solid var(--garis); background: #fff; border-radius: 10px; padding: .35rem .8rem; text-decoration: none; color: var(--hijau-tua); }
        .lapkas .penutup { font-size: .84rem; color: var(--tinta-muda); border-top: 1px solid var(--garis); margin-top: 1.6rem; padding-top: 1rem; }
    </style>

    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; Laporan Keuangan</div>

    <h1>Laporan Keuangan</h1>
    <p class="pengantar">
        Seluruh uang yang masuk dan keluar di Musholla Al Karim — dicatat langsung oleh pengurus,
        direkap per bulan supaya sisa saldo tiap bulan kelihatan bersambung.
    </p>
    <span class="cap-waktu">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        @if ($catatanTerakhir)
            Catatan terakhir {{ \Illuminate\Support\Carbon::parse($catatanTerakhir)->translatedFormat('d F Y') }}
        @else
            Belum ada catatan kas
        @endif
    </span>

    <div class="ringkas">
        <div class="kotak saldo">
            <span class="lbl">Saldo kas sekarang</span>
            <span class="angka">{{ $rupiah($saldoTerkini) }}</span>
            <span class="kecil">{{ $rupiah($masukSemua) }} masuk · {{ $rupiah($keluarSemua) }} keluar (seluruh catatan)</span>
        </div>
        <div class="kotak masuk">
            <span class="ikon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="m6 11 6-6 6 6"/></svg>
            </span>
            <span class="lbl">{{ $adaSaringan ? 'Pemasukan (tersaring)' : 'Pemasukan' }}</span>
            <span class="angka">{{ $rupiah($adaSaringan ? $masukSaring : $masukSemua) }}</span>
            <span class="kecil">{{ $jumlahMasuk }} catatan masuk</span>
        </div>
        <div class="kotak keluar">
            <span class="ikon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m18 13-6 6-6-6"/></svg>
            </span>
            <span class="lbl">{{ $adaSaringan ? 'Pengeluaran (tersaring)' : 'Pengeluaran' }}</span>
            <span class="angka">{{ $rupiah($adaSaringan ? $keluarSaring : $keluarSemua) }}</span>
            <span class="kecil">{{ $jumlahKeluar }} catatan keluar</span>
        </div>
    </div>

    @if ($masukSemua > 0)
        <div class="porsi">
            <div class="baris">
                <span>Pengeluaran memakai <strong>{{ $persenPakai }}%</strong> dari seluruh pemasukan</span>
                <span>Sisa <strong>{{ $rupiah($masukSemua - $keluarSemua) }}</strong> ({{ 100 - $persenPakai }}%)</span>
            </div>
            <div class="bar-porsi" role="img" aria-label="Pengeluaran {{ $persenPakai }} persen dari pemasukan">
                <i class="pakai" style="width: {{ $persenPakai }}%"></i><i class="sisa" style="width: {{ 100 - $persenPakai }}%"></i>
            </div>
            <div class="baris"><span>Dihitung dari seluruh catatan, bukan hanya yang tersaring.</span></div>
        </div>
    @endif

    <form class="saring" method="get" action="/laporan-kas">
        <label for="bulan">Bulan</label>
        <select name="bulan" id="bulan">
            <option value="">Semua bulan</option>
            @foreach ($daftarBulan as $b)
                <option value="{{ $b }}" @selected($bulan === $b)>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $b)->translatedFormat('F Y') }}</option>
            @endforeach
        </select>
        <label for="jenis">Jenis</label>
        <select name="jenis" id="jenis">
            <option value="">Semua jenis</option>
            <option value="masuk" @selected($jenis === 'masuk')>Pemasukan</option>
            <option value="keluar" @selected($jenis === 'keluar')>Pengeluaran</option>
        </select>
        @if ($daftarKategori->isNotEmpty())
            <label for="kategori">Kategori</label>
            <select name="kategori" id="kategori">
                <option value="">Semua kategori</option>
                @foreach ($daftarKategori as $k)
                    <option value="{{ $k }}" @selected($kategori === $k)>{{ $k }}</option>
                @endforeach
            </select>
        @endif
        <label for="cari">Cari keterangan</label>
        <input type="search" name="cari" id="cari" value="{{ $cari }}" placeholder="mis. PDAM, infaq">
        <button type="submit">Saring</button>
        @if ($adaSaringan)
            <a href="/laporan-kas">kosongkan</a>
        @endif
    </form>

    <h2 class="bagian">Rekap Bulanan</h2>
    <p class="sub">
        Tiap bulan punya saldo awal, uang masuk, uang keluar, dan saldo akhirnya sendiri —
        sisa bulan lalu otomatis menjadi saldo awal bulan berikutnya.
        @if ($tertinggi > 0) Panjang batang mengikuti nilai terbesar ({{ $rupiah($tertinggi) }}). @endif
    </p>

    <div class="rekap">
        @foreach ($rekapBulanan as $r)
            @if (($r['jenis'] ?? '') === 'kosong')
                <div class="bulan kosong">
                    {{ $r['dari'] }} – {{ $r['sampai'] }} · {{ $r['jumlah_bulan'] }} bulan tanpa catatan
                </div>
            @else
                <div class="bulan @if ($r['masuk'] == 0 && $r['keluar'] == 0) datar @endif">
                    <div class="nama">
                        <a href="/laporan-kas?bulan={{ $r['periode'] }}" title="Lihat rincian {{ $r['label'] }}">{{ $r['label'] }}</a>
                        @if ($r['berjalan'])
                            <small>bulan berjalan</small>
                        @elseif ($r['ditutup'])
                            <small>ditutup @if ($r['ditutup_at']) {{ $r['ditutup_at']->translatedFormat('d M Y') }} @endif</small>
                        @endif
                    </div>

                    <div class="dua">
                        <div class="baris-bar"><span>masuk</span>
                            <i class="masuk" style="width: {{ $tertinggi > 0 ? max(2, round($r['masuk'] / $tertinggi * 100)) : 2 }}%"></i></div>
                        <div class="baris-bar"><span>keluar</span>
                            <i class="keluar" style="width: {{ $tertinggi > 0 ? max(2, round($r['keluar'] / $tertinggi * 100)) : 2 }}%"></i></div>
                    </div>

                    <div class="angka">
                        <span class="naik">+ {{ $rupiah($r['masuk']) }}</span>
                        <em>− {{ $rupiah($r['keluar']) }}</em>
                    </div>

                    <div class="saldo">
                        saldo akhir
                        <b>{{ $rupiah($r['saldo_akhir']) }}</b>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <h2 class="bagian">Rincian Catatan</h2>
    <p class="sub">
        @if ($adaSaringan)
            Menampilkan catatan yang cocok dengan saringan — {{ $transaksi->total() }} catatan.
        @else
            {{ $transaksi->total() }} catatan, diurutkan dari yang terbaru.
        @endif
    </p>

    @if ($transaksi->isEmpty())
        <div class="buku">
            <p style="padding:1.6rem 1rem;text-align:center;color:var(--tinta-muda);font-size:.9rem;margin:0">
                @if ($adaSaringan)
                    Tidak ada catatan yang cocok dengan saringan ini.
                @else
                    Belum ada catatan kas. Pengurus dapat menambahkannya dari panel kelola.
                @endif
            </p>
        </div>
    @else
        <div class="buku">
            @foreach ($kelompok as $kunci => $daftar)
                <div class="kepala-bulan">
                    <span>
                        @if ($kunci === 'tanpa-tanggal')
                            Tanpa tanggal
                        @else
                            {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $kunci)->translatedFormat('F Y') }}
                        @endif
                    </span>
                    <span>{{ count($daftar) }} catatan</span>
                </div>

                @foreach ($daftar as $t)
                    <div class="catatan">
                        <div class="tgl">{{ $t->tanggal ? $t->tanggal->translatedFormat('d M') : '—' }}</div>
                        <div class="ket">
                            <b>{{ $t->keterangan ?: ($t->kategori ?: 'Tanpa keterangan') }}</b>
                            <span>
                                {{ $t->jenis === 'masuk' ? 'Pemasukan' : 'Pengeluaran' }}
                                @if ($t->keterangan && $t->kategori) · {{ $t->kategori }} @endif
                                @if ($t->pencatat) · dicatat {{ $t->pencatat->name }} @endif
                            </span>
                        </div>
                        <div class="nilai {{ $t->jenis === 'masuk' ? 'masuk' : 'keluar' }}">
                            {{ $t->jenis === 'masuk' ? '+' : '−' }} {{ $rupiah($t->jumlah) }}
                        </div>
                    </div>
                @endforeach
            @endforeach

            <div class="kaki-buku">
                <span>Halaman {{ $transaksi->currentPage() }} dari {{ $transaksi->lastPage() }}</span>
                <span>Saldo seluruh catatan: {{ $rupiah($saldoTerkini) }}</span>
            </div>
        </div>

        @if ($transaksi->hasPages())
            <div class="balik">
                @if (! $transaksi->onFirstPage())
                    <a href="{{ $transaksi->previousPageUrl() }}">‹ Sebelumnya</a>
                @endif
                <span>Halaman {{ $transaksi->currentPage() }} dari {{ $transaksi->lastPage() }}</span>
                @if ($transaksi->hasMorePages())
                    <a href="{{ $transaksi->nextPageUrl() }}">Berikutnya ›</a>
                @endif
            </div>
        @endif
    @endif

    <p class="penutup">
        Semua angka di halaman ini diambil langsung dari catatan kas pengurus di sistem musholla — tanpa perantara layanan luar.
        Bila ada pertanyaan mengenai rincian, silakan hubungi pengurus musholla.
    </p>
</div>
@endsection
