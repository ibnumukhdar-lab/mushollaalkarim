@extends('layouts.publik')

@section('judul', $hal->meta_judul ?: $hal->judul.' — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', $hal->meta_deskripsi ?: \App\Services\BersihkanTampilan::ringkas($hal->isi, 155))

@section('isi')
    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; {{ $hal->judul }}</div>
    <h1 class="judul-halaman">{{ $hal->judul }}</h1>

    <article class="kartu">
        <div class="isi-halaman">
            @if (trim(strip_tags(\App\Services\BersihkanTampilan::bersihkan($hal->isi))) === '')
                <p class="kosong">Halaman ini belum memiliki isi.</p>
            @else
                {!! \App\Services\BersihkanTampilan::bersihkan($hal->isi) !!}
            @endif
        </div>
    </article>
@endsection
