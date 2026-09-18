@extends('layouts.publik')

@section('judul', $tulisan->judul.' — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', \App\Services\BersihkanTampilan::ringkas($tulisan->ringkasan ?: $tulisan->isi, 155))

@section('isi')
    <div class="remah">
        <a href="/">Beranda</a> &nbsp;›&nbsp; <a href="/berita">Berita</a> &nbsp;›&nbsp; {{ $tulisan->judul }}
    </div>
    <h1 class="judul-halaman">{{ $tulisan->judul }}</h1>

    <article class="kartu">
        <div class="tanggal" style="font-size:.76rem;color:var(--tinta-muda);text-transform:uppercase;letter-spacing:.4px;margin-bottom:.7rem">
            {{ $tulisan->terbit_at?->translatedFormat('d F Y') }}
            @if ($tulisan->kategori) · {{ $tulisan->kategori }} @endif
        </div>
        <div class="isi-halaman">
            {!! \App\Services\BersihkanTampilan::bersihkan($tulisan->isi) !!}
        </div>
    </article>

    @if ($lain->isNotEmpty())
        <h2 class="bagian">Berita Lainnya</h2>
        <div class="jaring tiga">
            @foreach ($lain as $b)
                <a class="kartu" href="/berita/{{ $b->slug }}" style="color:inherit">
                    <div class="tanggal">{{ $b->terbit_at?->translatedFormat('d F Y') }}</div>
                    <h3>{{ $b->judul }}</h3>
                </a>
            @endforeach
        </div>
    @endif
@endsection
