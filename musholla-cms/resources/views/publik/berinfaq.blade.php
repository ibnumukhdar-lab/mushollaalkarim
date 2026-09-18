@extends('layouts.publik')

@section('judul', 'Mari Berinfaq — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', 'Salurkan infaq, sedekah, dan wakaf Anda untuk kemakmuran musholla Al Karim.')

@section('isi')
    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; Mari Berinfaq</div>
    <h1 class="judul-halaman">Mari Berinfaq</h1>

    @if ($terkirim)
        <div class="pesan-sukses">
            <strong>Terima kasih!</strong> Catatan infaq Anda sudah kami terima dan akan diperiksa pengurus.
            Bila perlu konfirmasi, pengurus akan menghubungi nomor WhatsApp yang Anda cantumkan.
        </div>
    @endif

    <div class="jaring dua" style="margin-bottom:1.4rem">
        <div class="kartu">
            <div class="tanggal">Terkumpul &amp; terverifikasi</div>
            <h3 style="font-size:1.3rem;margin:.2rem 0 0">Rp {{ number_format($totalTerverifikasi, 0, ',', '.') }}</h3>
            <p>Dari {{ number_format($jumlahDonatur, 0, ',', '.') }} donatur. Setiap catatan yang masuk diperiksa
                pengurus sebelum dicatat — lalu tampil di <a href="/laporan-kas">laporan keuangan</a>.</p>
        </div>
        <div class="kartu">
            <div class="tanggal">Cara menyalurkan</div>
            <p style="margin-top:.5rem">
                1. Isi formulir di bawah (nama, nominal, dan keterangan).<br>
                2. Pengurus memeriksa &amp; mencatatnya ke laporan keuangan.<br>
                3. Anda bisa mengunggah foto bukti transfer bila sudah menyalurkan.
            </p>
        </div>
    </div>

    @if ($hal && trim(strip_tags(\App\Services\BersihkanTampilan::bersihkan($hal->isi))) !== '')
        <div class="kartu" style="margin-bottom:1.4rem">
            <div class="isi-halaman">{!! \App\Services\BersihkanTampilan::bersihkan($hal->isi) !!}</div>
        </div>
    @endif

    @if ($wakaf->isNotEmpty())
        <h2 class="bagian">Program yang Bisa Dibantu</h2>
        <div class="jaring dua" style="margin-bottom:1.4rem">
            @foreach ($wakaf as $w)
                <div class="kartu">
                    <h3>{{ $w->nama }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $w->keterangan), 200) }}</p>
                    @if ($w->target > 0)
                        <p>Terkumpul <strong>Rp {{ number_format((float) $w->terkumpul, 0, ',', '.') }}</strong>
                            dari Rp {{ number_format((float) $w->target, 0, ',', '.') }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <h2 class="bagian">Formulir Infaq</h2>

    @if ($errors->any())
        <div class="pesan-galat">
            <strong>Ada yang perlu diperbaiki:</strong>
            <ul style="margin:.4rem 0 0 1.1rem">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form class="kartu form-kartu" method="post" action="/mari-berinfaq" enctype="multipart/form-data">
        @csrf
        <div class="jaring dua">
            <div class="baris">
                <label for="nama_donatur">Nama Anda <span class="wajib">*</span></label>
                <input type="text" id="nama_donatur" name="nama_donatur" value="{{ old('nama_donatur') }}" required maxlength="120">
            </div>
            <div class="baris">
                <label for="no_wa">Nomor WhatsApp <span class="wajib">*</span></label>
                <input type="tel" id="no_wa" name="no_wa" value="{{ old('no_wa') }}" required maxlength="25" placeholder="08xx">
            </div>
            <div class="baris">
                <label for="nominal">Nominal (Rp) <span class="wajib">*</span></label>
                <input type="number" id="nominal" name="nominal" value="{{ old('nominal') }}" required min="1000" step="1000">
            </div>
            <div class="baris">
                <label for="tujuan">Untuk</label>
                <select id="tujuan" name="tujuan">
                    <option value="Infaq umum">Infaq umum</option>
                    @foreach ($wakaf as $w)
                        <option value="{{ $w->nama }}" @selected(old('tujuan') === $w->nama)>{{ $w->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="baris">
            <label for="keterangan">Keterangan tambahan</label>
            <textarea id="keterangan" name="keterangan" rows="3" maxlength="1000" placeholder="mis. infaq atas nama keluarga, atau titipan untuk program tertentu">{{ old('keterangan') }}</textarea>
        </div>
        <div class="baris">
            <label for="bukti">Bukti transfer (foto, tidak wajib)</label>
            <input type="file" id="bukti" name="bukti" accept="image/*">
            <small>Format gambar, maksimal 2 MB.</small>
        </div>
        <button class="tombol-kirim" type="submit">Kirim catatan infaq</button>
    </form>
@endsection
