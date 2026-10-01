@extends('panel.layout')

@section('judul', 'Pengaturan WA Auto')
@section('remah', 'Panel Pengelola › Pusat WhatsApp › Pengaturan WA Auto')

@section('aksi')
    <a class="tbl tbl-samar wa-aksi-atas" href="{{ route('panel.wa.pusat') }}">@include('panel._ikon', ['nama' => 'wa']) <span class="wa-lbl">Kirim pesan</span></a>
    <a class="tbl tbl-samar wa-aksi-atas" href="{{ route('panel.wa.antrean') }}">@include('panel._ikon', ['nama' => 'kabar']) <span class="wa-lbl">Antrean</span></a>
    <a class="tbl tbl-samar wa-aksi-atas" href="{{ route('panel.wa.aturan') }}">@include('panel._ikon', ['nama' => 'putar']) <span class="wa-lbl">Aturan notifikasi</span></a>
@endsection

@section('isi')
<style>
    @media (max-width: 700px) {
        .kepala-aksi .tbl .wa-lbl { display: none !important; }
        .wa-aksi-atas .tbl { padding: .5rem .55rem; }
    }
    .wa-saklar { display: flex; align-items: center; gap: .7rem; font-size: .95rem; font-weight: 600; }
    .wa-saklar input { width: 20px; height: 20px; accent-color: var(--hijau); }
    .wa-bidang { margin-bottom: 1rem; }
    .wa-bidang label { display: block; font-size: .84rem; font-weight: 600; margin-bottom: .3rem; }
    .wa-bidang input { width: 100%; }
    .wa-bidang .bantuan { display: block; font-size: .78rem; color: var(--tinta-muda); margin-top: .3rem; line-height: 1.6; }
    .wa-hasil { margin-top: 1rem; border-radius: 12px; padding: .85rem 1rem; font-size: .86rem; line-height: 1.7; }
    .wa-hasil-ok { background: #e6f5ec; border: 1px solid #a9d8bd; color: #1d6b45; }
    .wa-hasil-gagal { background: #fdeceb; border: 1px solid #f0bcb8; color: #9c3232; }
    .wa-hasil code { background: #ffffff88; padding: .1rem .35rem; border-radius: 6px; font-size: .8rem; }
</style>

@if (session('sukses'))
    <div class="kartu" style="border-left:4px solid var(--hijau);margin-bottom:1rem">
        <p style="margin:0;font-size:.88rem">{{ session('sukses') }}</p>
    </div>
@endif

<div class="kartu">
    <div class="kartu-kepala"><h3>Status WA Auto</h3></div>
    <p style="margin:-.35rem 0 .7rem;font-size:.9rem;line-height:1.7">
        @if ($aktif)
            <span class="lencana lencana-hijau">aktif</span>
            Pesan terkirim sendiri lewat gateway setelah Anda menekan “Kirim”.
        @else
            <span class="lencana lencana-kuning">belum aktif</span>
            Mode manual tetap jalan (tautan WhatsApp per orang). Nyalakan saklar di bawah dan isi URL + token untuk
            pengiriman otomatis.
        @endif
    </p>
    <p style="margin:0;font-size:.83rem;color:var(--tinta-muda)">
        Antrean: <strong>{{ $ringkasan['menunggu'] }}</strong> menunggu ·
        <strong>{{ $ringkasan['terkirim'] }}</strong> terkirim ·
        <strong>{{ $ringkasan['gagal'] }}</strong> gagal
        @if ($nomorPengurus) · notifikasi ke <strong>{{ $nomorPengurus }}</strong> @endif
    </p>
</div>

<form class="kartu" method="post" action="{{ route('panel.wa.pengaturan.simpan') }}">
    @csrf

    <div class="kartu-kepala"><h3>Gateway pengiriman</h3></div>

    <div class="wa-bidang">
        <label class="wa-saklar">
            <input type="checkbox" name="wa_auto_aktif" value="1" @checked($p['wa_auto_aktif'] === '1')>
            Aktifkan pengiriman otomatis
        </label>
    </div>

    <div class="wa-bidang">
        <label for="wa_gateway_url">Link gateway</label>
        <input type="text" id="wa_gateway_url" name="wa_gateway_url" value="{{ $p['wa_gateway_url'] }}" maxlength="250" placeholder="https://api.dripsender.id/send">
    </div>

    <div class="wa-bidang">
        <label for="wa_gateway_token">Token / API key</label>
        <input type="text" id="wa_gateway_token" name="wa_gateway_token" value="{{ $p['wa_gateway_token'] }}" maxlength="250" autocomplete="off" placeholder="tempel token di sini">
    </div>

    <div class="pemisah"></div>
    <button class="tbl tbl-utama" type="submit">Simpan</button>
</form>

<form class="kartu" method="post" action="{{ route('panel.wa.uji') }}">
    @csrf
    <div class="kartu-kepala"><h3>Uji kirim</h3></div>
    <p style="margin:-.35rem 0 1rem;font-size:.85rem;color:var(--tinta-muda);line-height:1.7">
        Kirim satu pesan percobaan untuk memastikan token &amp; link benar. Percobaan ini tidak masuk antrean.
    </p>

    <div class="wa-bidang">
        <label for="uji_nomor">Nomor tujuan uji</label>
        <input type="text" id="uji_nomor" name="uji_nomor" maxlength="25" placeholder="0812xxxxxxx" required>
    </div>
    <div class="wa-bidang">
        <label for="uji_pesan">Isi pesan uji (opsional)</label>
        <input type="text" id="uji_pesan" name="uji_pesan" maxlength="500" placeholder="kosongkan = pesan bawaan">
    </div>

    <button class="tbl tbl-samar" type="submit">Kirim pesan uji</button>

    @if ($hasilUji)
        <div class="wa-hasil {{ $hasilUji['berhasil'] ? 'wa-hasil-ok' : 'wa-hasil-gagal' }}">
            <strong>{{ $hasilUji['berhasil'] ? 'Berhasil terkirim ✓' : 'Belum berhasil ✗' }}</strong>
            — nomor <code>{{ $hasilUji['nomor'] ?? '-' }}</code> pukul {{ $hasilUji['waktu'] ?? '' }}
            @if (! empty($hasilUji['galat']))
                <br>Keterangan: {{ $hasilUji['galat'] }}
            @endif
            @if (! empty($hasilUji['balasan']))
                <br>Balasan gateway: <code>{{ \Illuminate\Support\Str::limit($hasilUji['balasan'], 200) }}</code>
            @endif
        </div>
    @endif
</form>
@endsection
