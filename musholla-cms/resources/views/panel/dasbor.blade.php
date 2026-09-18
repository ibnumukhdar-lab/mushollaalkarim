@extends('panel.layout')

@section('judul', 'Dasbor')
@section('remah', 'Panel Pengelola')

@section('aksi')
    <a class="tbl tbl-utama" href="{{ route('panel.tambah', 'kas') }}">
        @include('panel._ikon', ['nama' => 'tambah']) Catat Kas
    </a>
@endsection

@section('isi')
    <div class="stat-baris">
        <div class="stat">
            <span class="ling ling-hijau">@include('panel._ikon', ['nama' => 'kas'])</span>
            <span>
                <span class="label">Saldo Kas</span>
                <span class="angka">Rp {{ number_format($stat['saldo'], 0, ',', '.') }}</span>
                <span class="kecil">Bulan ini: +{{ number_format($stat['kasMasuk'], 0, ',', '.') }} · −{{ number_format($stat['kasKeluar'], 0, ',', '.') }}</span>
            </span>
        </div>

        <div class="stat">
            <span class="ling ling-emas">@include('panel._ikon', ['nama' => 'infaq'])</span>
            <span>
                <span class="label">Infaq</span>
                <span class="angka">{{ $stat['infaqMenunggu'] }} menunggu</span>
                <span class="kecil">Terverifikasi bulan ini: Rp {{ number_format($stat['infaqBulan'], 0, ',', '.') }}</span>
            </span>
        </div>

        <div class="stat">
            <span class="ling ling-hijau">@include('panel._ikon', ['nama' => 'kabar'])</span>
            <span>
                <span class="label">Konten Situs</span>
                <span class="angka">{{ $stat['berita'] }} berita</span>
                <span class="kecil">{{ $stat['halaman'] }} halaman · {{ $stat['program'] }} program aktif</span>
            </span>
        </div>

        <div class="stat">
            <span class="ling ling-hijau">@include('panel._ikon', ['nama' => 'akun'])</span>
            <span>
                <span class="label">Jamaah</span>
                <span class="angka">{{ $stat['subscriber'] }} subscriber</span>
                <span class="kecil">{{ $stat['donatur'] }} donatur aktif · {{ $stat['admin'] }} pengelola</span>
            </span>
        </div>
    </div>

    <div class="kartu" style="margin-bottom:1.1rem">
        <div class="kartu-kepala">
            <h3>Langkah cepat</h3>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a class="tbl tbl-samar" href="{{ route('panel.tambah', 'berita') }}">@include('panel._ikon', ['nama' => 'kabar']) Tulis berita</a>
            <a class="tbl tbl-samar" href="{{ route('panel.tambah', 'kas') }}">@include('panel._ikon', ['nama' => 'kas']) Catat kas</a>
            <a class="tbl tbl-samar" href="{{ route('panel.tambah', 'infaq') }}">@include('panel._ikon', ['nama' => 'infaq']) Input infaq</a>
            <a class="tbl tbl-samar" href="{{ route('panel.tambah', 'program') }}">@include('panel._ikon', ['nama' => 'halaman']) Tambah program</a>
            <a class="tbl tbl-samar" href="{{ route('panel.daftar', 'users') }}">@include('panel._ikon', ['nama' => 'akun']) Lihat subscriber</a>
        </div>
    </div>

    <div class="kartu" style="margin-bottom:1.1rem">
        <div class="kartu-kepala">
            <h3>Kas terbaru</h3>
            <a class="tbl tbl-samar" href="{{ route('panel.daftar', 'kas') }}">Semua transaksi</a>
        </div>
        @if ($kasTerbaru->isEmpty())
            <p class="kosong">Belum ada catatan kas. Mulai dengan tombol “Catat Kas”.</p>
        @else
            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                    <tr><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th style="text-align:right">Jumlah</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($kasTerbaru as $k)
                        <tr>
                            <td data-label="Tanggal">{{ $k->tanggal?->translatedFormat('d M Y') }}</td>
                            <td data-label="Keterangan">
                                <span class="lencana {{ $k->jenis }}">{{ $k->jenis === 'masuk' ? 'Masuk' : 'Keluar' }}</span>
                                {{ $k->keterangan }}
                            </td>
                            <td data-label="Kategori">{{ $k->kategori ?: '—' }}</td>
                            <td data-label="Jumlah" class="angka">Rp {{ number_format((float) $k->jumlah, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="kartu" style="margin-bottom:1.1rem">
        <div class="kartu-kepala">
            <h3>Infaq terbaru</h3>
            <a class="tbl tbl-samar" href="{{ route('panel.daftar', 'infaq') }}">Semua infaq</a>
        </div>
        @if ($infaqTerbaru->isEmpty())
            <p class="kosong">Belum ada infaq tercatat.</p>
        @else
            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                    <tr><th>Tanggal</th><th>Donatur</th><th>Nominal</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($infaqTerbaru as $i)
                        <tr>
                            <td data-label="Tanggal">{{ $i->tanggal?->translatedFormat('d M Y') }}</td>
                            <td data-label="Donatur">{{ $i->nama_donatur }}</td>
                            <td data-label="Nominal" class="angka">Rp {{ number_format((float) $i->nominal, 0, ',', '.') }}</td>
                            <td data-label="Status">
                                @php
                                    $kelasStatus = match ($i->status) {
                                        'terverifikasi' => 'masuk',
                                        'ditolak' => 'merah',
                                        default => 'kuning',
                                    };
                                @endphp
                                <span class="lencana {{ $kelasStatus }}">{{ ucfirst($i->status) }}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="kartu">
        <div class="kartu-kepala">
            <h3>Program sepekan</h3>
            <a class="tbl tbl-samar" href="{{ route('panel.daftar', 'program') }}">Kelola program</a>
        </div>
        @if ($programHariIni->isEmpty())
            <p class="kosong">Belum ada program.</p>
        @else
            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead><tr><th>Hari</th><th>Waktu</th><th>Kegiatan</th></tr></thead>
                    <tbody>
                    @foreach ($programHariIni as $p)
                        <tr>
                            <td data-label="Hari">{{ $p->hari ?: '—' }}</td>
                            <td data-label="Waktu">{{ $p->waktu ?: '—' }}</td>
                            <td data-label="Kegiatan">{{ $p->nama }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
