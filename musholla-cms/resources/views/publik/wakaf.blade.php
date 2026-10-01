@extends('layouts.publik')

@php
    $namaProgram = $program->nama;
    $kalimatKeterangan = \App\Support\Tulis::ringkas((string) $program->keterangan, 155);
    $ringkasProgram = $kalimatKeterangan !== ''
        ? $kalimatKeterangan
        : ('Program wakaf '.$namaProgram
            .($program->target > 0
                ? ' — target Rp '.number_format((float) $program->target, 0, ',', '.').', terkumpul '.$program->persen.'%.'
                : ' untuk Musholla Al Karim.'));
@endphp

@section('judul', $namaProgram.' — Program Wakaf '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', $ringkasProgram)

@php
    // Kartu sosial 1200x630 dihitung SEKALI di sini (nama program + target + progres);
    // bila GD/font tidak tersedia, jatuh ke kartu bawaan situs.
    $ogGambar = \App\Services\KartuSosial::wakaf($program) ?: \App\Services\KartuSosial::bawaan();
@endphp

@section('og_tipe', 'website')
@section('og_gambar', $ogGambar)
@section('og_tipe_gambar', str_ends_with(strtolower($ogGambar), '.png') ? 'image/png' : 'image/jpeg')
@section('og_lebar', '1200')
@section('og_tinggi', '630')

@push('gaya')
<style>
    /* ---------- halaman program wakaf ---------- */
    .wakaf-halaman { max-width: 720px; margin: 0 auto; }
    .wakaf-halaman h1 { font-size: 1.5rem; line-height: 1.3; margin: .6rem 0 .5rem; color: var(--hijau-tua); }
    .wakaf-halaman .wakaf-gambar { max-width: 520px; margin: 0 auto 1.1rem; }
    .wakaf-status { display: inline-block; font-size: .74rem; font-weight: 700; letter-spacing: .4px;
        text-transform: uppercase; padding: .22rem .6rem; border-radius: 999px;
        background: var(--hijau-muda); color: var(--hijau-tua); }
    .wakaf-lini { height: 10px; border-radius: 6px; background: var(--hijau-muda); margin: .9rem 0 .5rem; overflow: hidden; }
    .wakaf-lini span { display: block; height: 100%; background: var(--hijau-lembut); }
    .wakaf-angka { font-size: .95rem; margin: 0; }
    .wakaf-rinci { margin: .3rem 0 .9rem; }
    .wakaf-rinci div { display: flex; justify-content: space-between; gap: 1rem; font-size: .9rem;
        padding: .45rem 0; border-bottom: 1px dashed var(--hijau-garis); }
    .wakaf-rinci dt { color: var(--tinta-muda); margin: 0; }
    .wakaf-rinci dd { margin: 0; font-weight: 600; color: var(--hijau-tua); }
    .wakaf-aksi { margin: 1.1rem 0 .3rem; }
    .wakaf-detail { font-size: .95rem; color: var(--tinta-muda); }
</style>
@endpush

@section('isi')
    <p style="margin:.2rem 0 0"><a href="{{ url('/mari-berinfaq') }}">← Semua cara berinfaq</a></p>

    <article class="kartu wakaf-halaman" style="margin-top:.9rem">
        @if ($program->gambar_url)
            <img class="wakaf-gambar" src="{{ $program->gambar_url }}" alt="Gambar {{ $namaProgram }}" loading="lazy">
        @endif

        <span class="wakaf-status">{{ $program->aktif ? 'Program wakaf — sedang berjalan' : 'Program wakaf — ditutup' }}</span>
        <h1>{{ $namaProgram }}</h1>

        @if ($program->ringkas_barang)
            <p class="wakaf-barang">{{ $program->ringkas_barang }}@if ($program->jumlah > 1 && $program->target > 0) — total Rp{{ number_format((float) $program->target, 0, ',', '.') }}@endif</p>
        @endif

        <h2 class="bagian" style="margin:1.1rem 0 .6rem">Kemajuan</h2>
        @if ($program->target > 0)
            <div class="wakaf-lini" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $program->persen }}" aria-label="Kemajuan program {{ $namaProgram }}">
                <span style="width:{{ $program->persen }}%"></span>
            </div>
            <p class="wakaf-angka">
                Terkumpul <strong>Rp {{ number_format((float) $program->terkumpul, 0, ',', '.') }}</strong>
                dari Rp {{ number_format((float) $program->target, 0, ',', '.') }} ({{ $program->persen }}%)
            </p>
            <p style="font-size:.86rem;color:var(--tinta-muda);margin:.3rem 0 0">
                Sisa Rp {{ number_format(max(0, (float) $program->target - (float) $program->terkumpul), 0, ',', '.') }} lagi.
            </p>
        @else
            <p class="wakaf-angka">Terkumpul <strong>Rp {{ number_format((float) $program->terkumpul, 0, ',', '.') }}</strong></p>
        @endif

        <h2 class="bagian" style="margin:1.2rem 0 .4rem">Rincian</h2>
        <dl class="wakaf-rinci">
            @if ($program->jumlah)
                <div>
                    <dt>Jumlah barang</dt>
                    <dd>{{ (int) $program->jumlah }}{{ trim((string) $program->satuan) !== '' ? ' '.trim((string) $program->satuan) : '' }}</dd>
                </div>
            @endif
            @if ((float) $program->harga_satuan > 0)
                <div><dt>Harga satuan</dt><dd>Rp {{ number_format((float) $program->harga_satuan, 0, ',', '.') }}</dd></div>
            @endif
            @if ($program->target > 0)
                <div><dt>Target dana</dt><dd>Rp {{ number_format((float) $program->target, 0, ',', '.') }}</dd></div>
            @endif
            <div><dt>Terkumpul</dt><dd>Rp {{ number_format((float) $program->terkumpul, 0, ',', '.') }}</dd></div>
            <div><dt>Status program</dt><dd>{{ $program->aktif ? 'Sedang berjalan' : 'Ditutup' }}</dd></div>
        </dl>

        @if (trim((string) $program->keterangan) !== '')
            <h2 class="bagian" style="margin:1.2rem 0 .4rem">Keterangan</h2>
            <div class="wakaf-detail">{!! nl2br(e(strip_tags((string) $program->keterangan))) !!}</div>
        @endif

        {{-- Tabel rincian kebutuhan: tampil bila program ini punya rincian
             (dipakai juga oleh halaman /infaq/<slug>). --}}
        @include('publik._rincian', ['program' => $program])

        <p class="wakaf-aksi">
            <a class="tombol-kecil" href="{{ url('/mari-berinfaq').'?tujuan='.rawurlencode($namaProgram).'#formInfaq' }}">Infaq untuk program ini</a>
        </p>
    </article>
@endsection
