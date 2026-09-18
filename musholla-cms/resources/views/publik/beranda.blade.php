@extends('layouts.publik')

@section('judul', ($pengaturan['nama_situs'] ?? 'Musholla Al Karim').' — '.($pengaturan['slogan'] ?? 'Kajian & Pendidikan Al-Qur\'an'))

@section('isi')
    <section class="pahlawan">
        <h1>{{ $pengaturan['nama_situs'] ?? 'Musholla Al Karim' }}</h1>
        <p>{{ $pengaturan['slogan'] ?? 'Mari makmurkan masjid — kajian rutin, pendidikan Al-Qur\'an, dan kegiatan sosial umat.' }}</p>
        <div class="aksi">
            <a class="tombol" href="/mari-berinfaq">Mari Berinfaq</a>
            <a class="tombol garis" href="/laporan-kas">Laporan Keuangan</a>
        </div>
    </section>

    @if ($hal)
        <section class="kartu">
            <div class="isi-halaman">
                {!! \App\Services\BersihkanTampilan::bersihkan($hal->isi) !!}
            </div>
        </section>
    @endif

    @if ($berita->isNotEmpty())
        <h2 class="bagian">Kabar & Berita</h2>
        <div class="jaring tiga">
            @foreach ($berita as $b)
                <a class="kartu" href="/berita/{{ $b->slug }}" style="color:inherit">
                    <div class="tanggal">{{ $b->terbit_at?->translatedFormat('d F Y') }}</div>
                    <h3>{{ $b->judul }}</h3>
                    <p>{{ \App\Services\BersihkanTampilan::ringkas($b->ringkasan ?: $b->isi, 120) }}</p>
                </a>
            @endforeach
        </div>
    @endif

    @if ($kajian->isNotEmpty())
        <h2 class="bagian">Jadwal Kajian</h2>
        <div class="jaring dua">
            @foreach ($kajian as $k)
                <div class="kartu">
                    <h3>{{ $k->judul }}</h3>
                    <p>
                        {{ $k->tanggal?->translatedFormat('d F Y') ?? 'Rutin' }}{{ $k->waktu_mulai ? ' · '.$k->waktu_mulai : '' }}
                        @if ($k->pemateri) <br>Pemateri: {{ $k->pemateri }} @endif
                        @if ($k->tempat) <br>Tempat: {{ $k->tempat }} @endif
                    </p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($wakaf->isNotEmpty())
        <h2 class="bagian">Program & Wakaf</h2>
        <div class="jaring dua">
            @foreach ($wakaf as $w)
                <div class="kartu">
                    <h3>{{ $w->nama }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $w->keterangan), 160) }}</p>
                    @if ($w->target > 0)
                        <p>
                            Terkumpul <strong>Rp{{ number_format((float) $w->terkumpul, 0, ',', '.') }}</strong>
                            dari Rp{{ number_format((float) $w->target, 0, ',', '.') }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
