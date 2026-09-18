@extends('layouts.publik')

@section('judul', $tulisan->judul.' — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', \App\Support\Tulis::ringkas($tulisan->ringkasan ?: $tulisan->isi, 155))

@php
    $gambarSampul = $tulisan->gambar_sampul;
    $kategoriTulisan = $tulisan->kategori_daftar;
@endphp

@section('isi')
    <div class="remah">
        <a href="/">Beranda</a> &nbsp;›&nbsp; <a href="/berita">Berita</a> &nbsp;›&nbsp; {{ $tulisan->judul }}
    </div>

    <article class="kartu">
        @if ($gambarSampul)
            <img src="{{ $gambarSampul }}" alt="{{ $tulisan->judul }}"
                 style="width:100%;max-width:420px;aspect-ratio:1/1;object-fit:cover;border-radius:14px;margin-bottom:1rem;display:block">
        @endif

        <h1 class="judul-halaman" style="margin-top:0">{{ $tulisan->judul }}</h1>

        <div class="tanggal" style="font-size:.78rem;color:var(--tinta-muda);text-transform:uppercase;letter-spacing:.4px;margin-bottom:.9rem">
            {{ $tulisan->terbit_at?->translatedFormat('d F Y') }}
            @if ($tulisan->penulis) · {{ $tulisan->penulis->nama_lengkap ?: $tulisan->penulis->name }} @endif
        </div>

        @if (! empty($kategoriTulisan))
            <div class="chip-baris" style="margin-bottom:1.1rem">
                @foreach ($kategoriTulisan as $namaKategori)
                    <span class="chip-kategori aktif">{{ $namaKategori }}</span>
                @endforeach
            </div>
        @endif

        <div class="isi-halaman">
            {!! \App\Support\Tulis::keHtml($tulisan->isi) !!}
        </div>
    </article>

    @if ($lain->isNotEmpty())
        <h2 class="bagian">Berita Lainnya</h2>
        <div class="jaring tiga">
            @foreach ($lain as $b)
                <article class="berita-kartu" style="flex:none">
                    <a class="berita-kartu-tautan" href="/berita/{{ $b->slug }}">
                        <span class="berita-gambar">
                            @if ($b->gambar_sampul)
                                <img src="{{ $b->gambar_sampul }}" alt="{{ $b->judul }}" loading="lazy">
                            @else
                                <span class="berita-gambar-kosong">@include('publik._ikon', ['nama' => 'masjid'])</span>
                            @endif
                        </span>
                        <span class="berita-isi">
                            <span class="tanggal">{{ $b->terbit_at?->translatedFormat('d F Y') }}</span>
                            <h3>{{ $b->judul }}</h3>
                        </span>
                    </a>
                </article>
            @endforeach
        </div>
    @endif
@endsection
