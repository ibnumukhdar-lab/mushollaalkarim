{{--
    Blok cara menyalurkan infaq (rekening + QRIS).
    Dipakai dua kali di halaman Mari Berinfaq: di halaman dan di dalam popup.
    Tombol salin memakai atribut data supaya aman dipakai berulang (tanpa id ganda).
--}}
@php
    $bankTujuan = $pengaturan['rekening_bank'] ?? '';
    $nomorTujuan = $pengaturan['rekening_nomor'] ?? '';
    $namaTujuan = $pengaturan['rekening_nama'] ?? '';
    $qrisGambar = $pengaturan['qris_path'] ?? '';
    $catatanInfaq = $pengaturan['infaq_catatan'] ?? '';
@endphp

@if ($nomorTujuan !== '')
    <div class="infaq-bank">
        <div class="bank">{{ $bankTujuan ?: 'Transfer bank' }}</div>
        <div class="nomor">{{ $nomorTujuan }}</div>
        @if ($namaTujuan !== '')
            <div class="atas">a.n. {{ $namaTujuan }}</div>
        @endif
        <div class="baris-salin">
            <button type="button" class="tombol-salin" data-salin="{{ $nomorTujuan }}">Salin nomor rekening</button>
            @if ($bankTujuan !== '')
                <span style="font-size:.78rem;color:var(--tinta-muda)">Tanpa biaya admin antar bank</span>
            @endif
        </div>
    </div>
@endif

@if ($qrisGambar !== '')
    <div class="infaq-qris">
        <p class="infaq-label" style="margin-bottom:.5rem">Pindai QRIS</p>
        <img src="{{ url('/berkas/' . ltrim($qrisGambar, '/')) }}" alt="QRIS {{ $pengaturan['nama_situs'] ?? 'Musholla Al Karim' }}" loading="lazy">
        <p class="ket">Bisa dipindai dari aplikasi m-banking atau e-wallet apa pun yang mendukung QRIS.</p>
    </div>
@endif

@if ($nomorTujuan === '' && $qrisGambar === '')
    <div class="kartu" style="margin-bottom:1rem">
        <p class="kosong" style="margin:0">Rekening &amp; QRIS infaq sedang disiapkan pengurus. Sementara itu, silakan hubungi pengurus
            @if (! empty($pengaturan['kontak_wa']))
                di <a href="https://wa.me/{{ preg_replace('/\D/', '', $pengaturan['kontak_wa']) }}" target="_blank" rel="noopener">WhatsApp pengurus</a>
            @endif
            untuk menyalurkan infaq.</p>
    </div>
@endif

@if ($catatanInfaq !== '')
    <p style="font-size:.85rem;color:var(--tinta-muda);margin:.2rem 0 1rem">{{ $catatanInfaq }}</p>
@endif
