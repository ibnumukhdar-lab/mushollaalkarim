@extends('layouts.publik')

@section('judul', 'Berita — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))

@section('isi')
    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; Berita</div>
    <h1 class="judul-halaman">Kabar & Berita</h1>

    @if ($daftar->isEmpty())
        <div class="kartu"><p class="kosong">Belum ada berita yang diterbitkan.</p></div>
    @else
        <div class="jaring tiga">
            @foreach ($daftar as $b)
                <a class="kartu" href="/berita/{{ $b->slug }}" style="color:inherit">
                    <div class="tanggal">{{ $b->terbit_at?->translatedFormat('d F Y') }}</div>
                    <h3>{{ $b->judul }}</h3>
                    <p>{{ \App\Services\BersihkanTampilan::ringkas($b->ringkasan ?: $b->isi, 130) }}</p>
                </a>
            @endforeach
        </div>

        @if ($daftar->hasPages())
            <div style="margin-top:1.4rem; display:flex; gap:.6rem; flex-wrap:wrap">
                @if ($daftar->onFirstPage())
                    <span class="tombol garis" style="background:#fff;color:var(--tinta-muda);border-color:var(--garis)">‹ Sebelumnya</span>
                @else
                    <a class="tombol" style="background:var(--navy);color:#fff" href="{{ $daftar->previousPageUrl() }}">‹ Sebelumnya</a>
                @endif
                @if ($daftar->hasMorePages())
                    <a class="tombol" style="background:var(--navy);color:#fff" href="{{ $daftar->nextPageUrl() }}">Berikutnya ›</a>
                @endif
            </div>
        @endif
    @endif
@endsection
