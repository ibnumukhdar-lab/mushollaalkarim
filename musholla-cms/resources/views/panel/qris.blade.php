@extends('panel.layout')

@section('judul', 'Infaq & QRIS')
@section('remah', 'Panel Pengelola › Infaq & QRIS')

@section('isi')
    <form class="kartu" method="post" action="{{ route('panel.qris.simpan') }}" enctype="multipart/form-data">
        @csrf

        <div class="kartu-kepala">
            <h3>Rekening & QRIS untuk infaq</h3>
        </div>
        <p style="margin:-.35rem 0 1rem;font-size:.84rem;color:var(--tinta-muda)">
            Isian ini yang tampil di halaman <strong>Mari Berinfaq</strong> (rekening bank yang bisa disalin dan gambar QRIS untuk dipindai).
        </p>

        <div class="jaring-2">
            <div class="bidang">
                <label for="rekening_bank">Bank / penyedia</label>
                <input type="text" id="rekening_bank" name="rekening_bank" value="{{ $nilai['rekening_bank'] ?? '' }}" placeholder="mis. Bank Jago Syariah">
            </div>
            <div class="bidang">
                <label for="rekening_nomor">Nomor rekening</label>
                <input type="text" id="rekening_nomor" name="rekening_nomor" value="{{ $nilai['rekening_nomor'] ?? '' }}" placeholder="mis. 507531817035" inputmode="numeric">
            </div>
            <div class="bidang lebar-penuh">
                <label for="rekening_nama">Atas nama</label>
                <input type="text" id="rekening_nama" name="rekening_nama" value="{{ $nilai['rekening_nama'] ?? '' }}" placeholder="mis. Fahrizal">
            </div>
        </div>

        <div class="pemisah"></div>

        @include('partials.unggah-gambar', [
            'nama' => 'qris',
            'label' => 'Gambar QRIS',
            'nilai' => $nilai['qris_path'] ?? null,
            'mode' => 'potong',
        ])

        <div class="bidang lebar-penuh">
            <label for="infaq_catatan">Catatan tambahan untuk donatur</label>
            <textarea id="infaq_catatan" name="infaq_catatan" rows="3" placeholder="mis. Mohon cantumkan nama saat transfer, lalu isi konfirmasi di situs.">{{ $nilai['infaq_catatan'] ?? '' }}</textarea>
            <span class="bantuan">Tampil sebagai keterangan kecil di halaman Mari Berinfaq.</span>
        </div>

        <div class="kaki-form">
            <button class="tbl tbl-utama" type="submit">Simpan</button>
            <a class="tbl tbl-samar" href="{{ route('panel.daftar', 'infaq') }}">Ke daftar infaq</a>
        </div>
    </form>

    <div class="kartu" style="margin-top:1.1rem">
        <div class="kartu-kepala">
            <h3>Pratinjau QRIS tersimpan</h3>
            @if (! empty($nilai['qris_path']))
                <form method="post" action="{{ route('panel.qris.hapus') }}" onsubmit="return confirm('Hapus gambar QRIS? Halaman infaq tidak akan menampilkan QRIS lagi.')" style="margin:0">
                    @csrf
                    @method('DELETE')
                    <button class="tbl tbl-samar" type="submit">Hapus QRIS</button>
                </form>
            @endif
        </div>
        @if (! empty($nilai['qris_path']))
            <img src="{{ url('/berkas/' . ltrim($nilai['qris_path'], '/')) }}" alt="QRIS Musholla Al Karim"
                 style="max-width:260px;width:100%;border:1px solid var(--garis);border-radius:14px;background:#fff;padding:.5rem">
        @else
            <p class="kosong">Belum ada gambar QRIS. Pilih gambar di atas lalu tekan “Potong &amp; pakai”, kemudian Simpan.</p>
        @endif
    </div>
@endsection
