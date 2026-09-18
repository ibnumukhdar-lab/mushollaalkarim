@extends('layouts.publik')

@section('judul', 'Laporan Keuangan — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', 'Transparansi kas Musholla Al Karim: pemasukan, pengeluaran, dan saldo — dicatat langsung oleh pengurus.')

@section('isi')
    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; Laporan Keuangan</div>
    <h1 class="judul-halaman">Laporan Keuangan</h1>

    <style>
        .kas-ringkas { display: grid; gap: .9rem; margin-bottom: 1.3rem; }
        @media (min-width: 640px) { .kas-ringkas { grid-template-columns: repeat(3, 1fr); } }
        .kas-kotak { border-radius: 14px; padding: 1.05rem 1.15rem; color: #fff; }
        .kas-kotak .label { font-size: .72rem; text-transform: uppercase; letter-spacing: .6px; opacity: .88; }
        .kas-kotak .angka { font-size: 1.32rem; font-weight: 700; margin-top: .25rem; }
        .kas-masuk { background: linear-gradient(135deg, #1f7a4d, #2f9e68); }
        .kas-keluar { background: linear-gradient(135deg, #a4373f, #c0505a); }
        .kas-saldo { background: linear-gradient(160deg, var(--navy), var(--navy-lembut)); }
        .kas-saring { display: flex; flex-wrap: wrap; gap: .55rem; align-items: flex-end; margin-bottom: 1.2rem; }
        .kas-saring label { display: block; font-size: .74rem; color: var(--tinta-muda); margin-bottom: .2rem; }
        .kas-saring select, .kas-saring input {
            font: inherit; font-size: .86rem; padding: .42rem .6rem; border: 1px solid var(--garis);
            border-radius: 9px; background: #fff; color: var(--tinta); min-width: 8.5rem;
        }
        .kas-saring input[type=search] { min-width: 10rem; }
        .kas-saring button {
            font: inherit; font-size: .86rem; font-weight: 600; padding: .45rem .95rem; border: 0;
            border-radius: 9px; background: var(--navy); color: #fff; cursor: pointer;
        }
        .kas-saring a.kosongkan { font-size: .82rem; color: var(--tinta-muda); align-self: center; }
        .kas-grafik { display: flex; align-items: flex-end; gap: .35rem; height: 130px; padding: .8rem .3rem 0; overflow-x: auto; }
        .kas-batang { flex: 1 0 2.2rem; display: flex; flex-direction: column; align-items: center; gap: .25rem; }
        .kas-batang .dua { display: flex; gap: 2px; align-items: flex-end; height: 100px; }
        .kas-batang .dua i { display: block; width: 9px; border-radius: 3px 3px 0 0; }
        .kas-batang .masuk { background: #2f9e68; }
        .kas-batang .keluar { background: #c0505a; }
        .kas-batang small { font-size: .64rem; color: var(--tinta-muda); white-space: nowrap; }
        .kas-tabel { width: 100%; border-collapse: collapse; font-size: .9rem; }
        .kas-tabel th { text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .5px;
            color: var(--tinta-muda); padding: .6rem .7rem; border-bottom: 1px solid var(--garis); }
        .kas-tabel td { padding: .6rem .7rem; border-bottom: 1px solid var(--garis); vertical-align: top; }
        .kas-tabel td.angka { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .masuk-teks { color: #1f7a4d; font-weight: 600; }
        .keluar-teks { color: #a4373f; font-weight: 600; }
        .kas-aksi { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: 1.2rem; }
        @media (max-width: 700px) {
            .kas-tabel thead { display: none; }
            .kas-tabel tr { display: block; border: 1px solid var(--garis); border-radius: 12px;
                padding: .7rem .8rem; margin-bottom: .6rem; background: #fff; }
            .kas-tabel td { display: flex; justify-content: space-between; gap: 1rem; border: 0; padding: .22rem 0; }
            .kas-tabel td::before { content: attr(data-label); font-size: .74rem; color: var(--tinta-muda); }
            .kas-tabel td.angka { text-align: right; }
        }
    </style>

    <div class="kas-ringkas">
        <div class="kas-kotak kas-masuk">
            <div class="label">Pemasukan</div>
            <div class="angka">Rp {{ number_format($masukSaring, 0, ',', '.') }}</div>
        </div>
        <div class="kas-kotak kas-keluar">
            <div class="label">Pengeluaran</div>
            <div class="angka">Rp {{ number_format($keluarSaring, 0, ',', '.') }}</div>
        </div>
        <div class="kas-kotak kas-saldo">
            <div class="label">Saldo Akhir</div>
            <div class="angka">Rp {{ number_format($masukSemua - $keluarSemua, 0, ',', '.') }}</div>
        </div>
    </div>

    @if ($bulan || $jenis || $kategori || $cari !== '')
        <p style="font-size:.84rem;color:var(--tinta-muda);margin:-.6rem 0 1.1rem">
            Angka di atas mengikuti saringan yang dipilih. Saldo akhir selalu dihitung dari seluruh catatan:
            Rp {{ number_format($masukSemua, 0, ',', '.') }} masuk · Rp {{ number_format($keluarSemua, 0, ',', '.') }} keluar.
        </p>
    @endif

    <form class="kas-saring" method="get" action="/laporan-kas">
        <div>
            <label for="bulan">Bulan</label>
            <select name="bulan" id="bulan">
                <option value="">Semua bulan</option>
                @foreach ($daftarBulan as $b)
                    <option value="{{ $b }}" @selected($bulan === $b)>
                        {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $b)->translatedFormat('F Y') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="jenis">Jenis</label>
            <select name="jenis" id="jenis">
                <option value="">Semua transaksi</option>
                <option value="masuk" @selected($jenis === 'masuk')>Pemasukan</option>
                <option value="keluar" @selected($jenis === 'keluar')>Pengeluaran</option>
            </select>
        </div>
        @if ($daftarKategori->isNotEmpty())
            <div>
                <label for="kategori">Kategori</label>
                <select name="kategori" id="kategori">
                    <option value="">Semua kategori</option>
                    @foreach ($daftarKategori as $k)
                        <option value="{{ $k }}" @selected($kategori === $k)>{{ $k }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label for="cari">Cari keterangan</label>
            <input type="search" name="cari" id="cari" value="{{ $cari }}" placeholder="mis. listrik">
        </div>
        <button type="submit">Terapkan</button>
        @if ($bulan || $jenis || $kategori || $cari !== '')
            <a class="kosongkan" href="/laporan-kas">kosongkan saringan</a>
        @endif
    </form>

    @php
        $tertinggi = 0;
        foreach ($rekap as $r) { $tertinggi = max($tertinggi, $r['masuk'], $r['keluar']); }
    @endphp

    @if ($tertinggi > 0)
        <h2 class="bagian">Rekap 12 Bulan Terakhir</h2>
        <div class="kartu" style="padding-bottom:.9rem">
            <div class="kas-grafik">
                @foreach ($rekap as $kunci => $r)
                    <div class="kas-batang">
                        <div class="dua">
                            <i class="masuk" style="height: {{ $tertinggi > 0 ? max(2, round($r['masuk'] / $tertinggi * 100)) : 2 }}%"></i>
                            <i class="keluar" style="height: {{ $tertinggi > 0 ? max(2, round($r['keluar'] / $tertinggi * 100)) : 2 }}%"></i>
                        </div>
                        <small>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $kunci)->translatedFormat('M y') }}</small>
                    </div>
                @endforeach
            </div>
            <p style="font-size:.76rem;color:var(--tinta-muda);margin:.7rem 0 0">
                <span style="display:inline-block;width:9px;height:9px;background:#2f9e68;border-radius:2px"></span> pemasukan &nbsp;
                <span style="display:inline-block;width:9px;height:9px;background:#c0505a;border-radius:2px"></span> pengeluaran
            </p>
        </div>
    @endif

    <h2 class="bagian">Rincian Transaksi</h2>

    @if ($transaksi->isEmpty())
        <div class="kartu">
            <p class="kosong">
                @if ($bulan || $jenis || $kategori || $cari !== '')
                    Tidak ada transaksi yang cocok dengan saringan ini.
                @else
                    Belum ada catatan kas. Pengurus dapat menambahkannya dari panel kelola.
                @endif
            </p>
        </div>
    @else
        <div class="kartu" style="padding:.4rem .5rem">
            <table class="kas-tabel">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Kategori</th>
                        <th style="text-align:right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transaksi as $t)
                        <tr>
                            <td data-label="Tanggal">{{ $t->tanggal?->translatedFormat('d M Y') }}</td>
                            <td data-label="Keterangan">{{ $t->keterangan ?: '—' }}</td>
                            <td data-label="Kategori">{{ $t->kategori ?: '—' }}</td>
                            <td data-label="Jumlah" class="angka {{ $t->jenis === 'masuk' ? 'masuk-teks' : 'keluar-teks' }}">
                                {{ $t->jenis === 'masuk' ? '+' : '−' }} Rp {{ number_format((float) $t->jumlah, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($transaksi->hasPages())
            <div class="kas-aksi">
                @if (! $transaksi->onFirstPage())
                    <a class="tombol" style="background:var(--navy);color:#fff" href="{{ $transaksi->previousPageUrl() }}">‹ Sebelumnya</a>
                @endif
                <span style="align-self:center;font-size:.82rem;color:var(--tinta-muda)">
                    Halaman {{ $transaksi->currentPage() }} dari {{ $transaksi->lastPage() }}
                </span>
                @if ($transaksi->hasMorePages())
                    <a class="tombol" style="background:var(--navy);color:#fff" href="{{ $transaksi->nextPageUrl() }}">Berikutnya ›</a>
                @endif
            </div>
        @endif
    @endif

    <p style="font-size:.82rem;color:var(--tinta-muda);margin-top:1.4rem">
        Seluruh angka di halaman ini diambil langsung dari catatan kas pengurus di sistem musholla —
        tanpa perantara layanan luar. Bila ada pertanyaan mengenai rincian, silakan hubungi pengurus musholla.
    </p>
@endsection
