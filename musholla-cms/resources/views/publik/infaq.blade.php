@extends('layouts.publik')

@php
    $namaProgram = $program->nama;
    $periode = trim((string) $program->periode_label);
    $kebutuhan = (float) $program->kebutuhan;
    $progres = (float) $program->progres;
    $persen = (int) $program->persen_kebutuhan;
    $dariKas = trim((string) $program->kata_kunci_kas) !== '';
    $labelProgres = $dariKas ? 'Terkumpul bulan ini' : 'Terkumpul';
    // "Kebutuhan setiap bulan" → "setiap bulan" agar enak dibaca di dalam kalimat
    $periodePendek = trim(preg_replace('~^kebutuhan\s+~i', '', $periode) ?? $periode);

    $kalimatSingkat = \App\Support\Tulis::ringkas((string) $program->keterangan, 155);
    $ringkasProgram = $kalimatSingkat !== ''
        ? $kalimatSingkat
        : ('Ajakan infaq '.$namaProgram
            .($kebutuhan > 0 ? ' — kebutuhan Rp '.number_format($kebutuhan, 0, ',', '.').($periode !== '' ? ' '.$periode : '').'.' : '.'));
@endphp

@section('judul', $namaProgram.' — Ajakan Infaq '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', $ringkasProgram)

@php
    // Kartu sosial 1200x630 (nama program + kebutuhan + progres); bila GD/font tak ada,
    // jatuh ke kartu bawaan situs — sama polanya dengan halaman program wakaf.
    $ogGambar = \App\Services\KartuSosial::wakaf($program) ?: \App\Services\KartuSosial::bawaan();
@endphp

@section('og_tipe', 'website')
@section('og_gambar', $ogGambar)
@section('og_tipe_gambar', str_ends_with(strtolower($ogGambar), '.png') ? 'image/png' : 'image/jpeg')
@section('og_lebar', '1200')
@section('og_tinggi', '630')

@push('gaya')
<style>
    /* ---------- halaman ajakan infaq ---------- */
    .infaq-halaman { max-width: 760px; margin: 0 auto; }
    .infaq-halaman h1 { font-size: 1.5rem; line-height: 1.3; margin: .6rem 0 .5rem; color: var(--hijau-tua); }
    .infaq-halaman .infaq-gambar { display: block; width: 100%; max-width: 520px; aspect-ratio: 1 / 1;
        object-fit: cover; border-radius: 10px; margin: 0 auto 1.1rem; background: var(--hijau-muda); }
    .infaq-status { display: inline-block; font-size: .74rem; font-weight: 700; letter-spacing: .4px;
        text-transform: uppercase; padding: .22rem .6rem; border-radius: 999px;
        background: var(--hijau-muda); color: var(--hijau-tua); }
    .infaq-ajakan { font-size: .95rem; color: var(--tinta-muda); margin: .2rem 0 1rem; }
    .infaq-lini { height: 10px; border-radius: 6px; background: var(--hijau-muda); margin: .9rem 0 .5rem; overflow: hidden; }
    .infaq-lini span { display: block; height: 100%; background: var(--hijau-lembut); }
    .infaq-angka { font-size: .95rem; margin: 0; }
    .infaq-sisa { font-size: .86rem; color: var(--tinta-muda); margin: .3rem 0 0; }
    .infaq-detail { font-size: .95rem; color: var(--tinta-muda); }
    .infaq-aksi { display: flex; gap: .6rem; flex-wrap: wrap; align-items: center; margin: 1.2rem 0 .4rem; }
    .infaq-catatan-kaki { font-size: .82rem; color: var(--tinta-muda); line-height: 1.7; margin-top: .8rem; }
</style>
@endpush

@section('isi')
    <p style="margin:.2rem 0 0"><a href="{{ url('/mari-berinfaq') }}">← Semua cara berinfaq</a></p>

    <article class="kartu infaq-halaman" style="margin-top:.9rem">
        @if ($program->gambar_url)
            <img class="infaq-gambar" src="{{ $program->gambar_url }}" alt="Gambar {{ $namaProgram }}" loading="lazy">
        @endif

        <span class="infaq-status">{{ $program->aktif ? 'Ajakan infaq — sedang berjalan' : 'Ajakan infaq — ditutup' }}</span>
        <h1>{{ $namaProgram }}</h1>

        @if ($kebutuhan > 0 || $progres > 0)
            <h2 class="bagian" style="margin:1rem 0 .6rem">Kemajuan</h2>

            @if ($kebutuhan > 0)
                <div class="infaq-lini" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                     aria-valuenow="{{ $persen }}" aria-label="Kemajuan {{ $namaProgram }}">
                    <span style="width:{{ $persen }}%"></span>
                </div>
            @endif

            <p class="infaq-angka">
                {{ $labelProgres }} <strong>Rp {{ number_format($progres, 0, ',', '.') }}</strong>
                @if ($kebutuhan > 0)
                    dari kebutuhan <strong>Rp {{ number_format($kebutuhan, 0, ',', '.') }}</strong>
                    @if ($periodePendek !== '') {{ strtolower($periodePendek) }} @endif
                    ({{ $persen }}%)
                @endif
            </p>

            @if ($kebutuhan > $progres)
                <p class="infaq-sisa">Masih kurang Rp {{ number_format($kebutuhan - $progres, 0, ',', '.') }}.</p>
            @elseif ($kebutuhan > 0)
                <p class="infaq-sisa">Alhamdulillah, kebutuhan sudah terpenuhi — dana lebihnya tetap dipakai untuk operasional musholla.</p>
            @endif
        @endif

        @include('publik._rincian', ['program' => $program])

        @if (trim((string) $program->keterangan) !== '')
            <h2 class="bagian" style="margin:1.2rem 0 .4rem">Kenapa perlu?</h2>
            <div class="infaq-detail">{!! nl2br(e(strip_tags((string) $program->keterangan))) !!}</div>
        @endif

        <p class="infaq-aksi">
            <a class="tombol-kecil" href="{{ url('/mari-berinfaq').'?tujuan='.rawurlencode($namaProgram).'#formInfaq' }}">Infaq untuk program ini</a>
            <a class="tombol-kecil" style="background:#25d366;border-color:#25d366"
               href="https://wa.me/?text={{ rawurlencode('*Infaq '.$namaProgram.'*'."\n".($kebutuhan > 0 ? 'Kebutuhan: Rp '.number_format($kebutuhan, 0, ',', '.').($periode !== '' ? ' '.$periode : '')."\n" : '').($kebutuhan > 0 ? 'Terkumpul: Rp '.number_format($progres, 0, ',', '.').' ('.$persen.'%)'."\n" : '')."\n".'Rincian & cara berinfaq:'."\n".url('/infaq/'.$program->slug)) }}"
               target="_blank" rel="noopener">Bagikan ke WhatsApp</a>
        </p>

        <p class="infaq-catatan-kaki">
            Rincian di atas menggambarkan kebutuhan rutin musholla; setiap rupiah yang masuk dicatat di
            <a href="{{ url('/laporan-kas') }}">laporan keuangan</a> yang bisa dilihat siapa saja.
            Nomor rekening, QRIS, dan formulir konfirmasi ada di <a href="{{ url('/mari-berinfaq') }}">Mari Berinfaq</a>.
        </p>
    </article>
@endsection
