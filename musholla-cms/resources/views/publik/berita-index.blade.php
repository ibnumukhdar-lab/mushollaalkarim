@extends('layouts.publik')

@section('judul', 'Berita — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', 'Kabar, kajian, dan kegiatan Musholla Al Karim.')

@section('isi')
    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; Berita</div>
    <h1 class="judul-halaman">Kabar &amp; Berita</h1>

    <form class="cari-berita" method="get" action="/berita">
        <input type="search" name="q" value="{{ $cari }}" placeholder="Cari kabar atau kegiatan…" aria-label="Cari berita">
        <button class="tombol-kecil" type="submit">Cari</button>
        @if ($cari !== '')
            <a class="tombol-kecil" href="/berita">Bersihkan</a>
        @endif
    </form>

    @if ($kategori->isNotEmpty())
        <div class="chip-baris">
            <a href="/berita{{ $cari !== '' ? '?q=' . urlencode($cari) : '' }}"
               @class(['chip-kategori', 'aktif' => $kategoriAktif === ''])>Semua</a>
            @foreach ($kategori as $k)
                <a href="/berita?kategori={{ $k->slug }}{{ $cari !== '' ? '&q=' . urlencode($cari) : '' }}"
                   @class(['chip-kategori', 'aktif' => $kategoriAktif === $k->slug])>{{ $k->nama }}</a>
            @endforeach
        </div>
    @endif

    @if ($daftar->isEmpty())
        <div class="kartu"><p class="kosong">Belum ada berita yang sesuai.</p></div>
    @else
        <div class="jaring tiga">
            @foreach ($daftar as $b)
                @php
                    $gambarSampul = $b->gambar_sampul;
                    $chipKategori = $b->kategori_utama;
                    $ringkasBerita = \App\Support\Tulis::ringkas($b->ringkasan ?: $b->isi, 130);
                @endphp
                <article class="berita-kartu" style="flex:none">
                    <a class="berita-kartu-tautan" href="/berita/{{ $b->slug }}">
                        <span class="berita-gambar">
                            @if ($gambarSampul)
                                <img src="{{ $gambarSampul }}" alt="{{ $b->judul }}" loading="lazy">
                            @else
                                <span class="berita-gambar-kosong">@include('publik._ikon', ['nama' => 'masjid'])</span>
                            @endif
                            @if ($chipKategori)
                                <span class="berita-chip">{{ $chipKategori }}</span>
                            @endif
                        </span>
                        <span class="berita-isi">
                            <span class="tanggal">{{ $b->terbit_at?->translatedFormat('d F Y') }}</span>
                            <h3>{{ $b->judul }}</h3>
                            @if ($ringkasBerita)
                                <span class="berita-ringkas">{{ $ringkasBerita }}</span>
                            @endif
                        </span>
                    </a>
                </article>
            @endforeach
        </div>

        @if ($daftar->hasPages())
            <div class="halaman">
                @if ($daftar->onFirstPage())
                    <span>‹</span>
                @else
                    <a href="{{ $daftar->previousPageUrl() }}">‹ Sebelumnya</a>
                @endif
                <span class="aktif">{{ $daftar->currentPage() }}</span>
                <span>dari {{ $daftar->lastPage() }}</span>
                @if ($daftar->hasMorePages())
                    <a href="{{ $daftar->nextPageUrl() }}">Berikutnya ›</a>
                @endif
            </div>
        @endif
    @endif
@endsection
