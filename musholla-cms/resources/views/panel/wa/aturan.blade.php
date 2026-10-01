@extends('panel.layout')

@section('judul', 'Aturan Notifikasi WhatsApp')
@section('remah', 'Panel Pengelola › Pusat WhatsApp › Aturan Notifikasi')

@section('aksi')
    <a class="tbl tbl-samar wa-aksi-atas" href="{{ route('panel.wa.pusat') }}">@include('panel._ikon', ['nama' => 'wa']) <span class="wa-lbl">Kirim pesan</span></a>
    <a class="tbl tbl-samar wa-aksi-atas" href="{{ route('panel.wa.antrean') }}">@include('panel._ikon', ['nama' => 'kabar']) <span class="wa-lbl">Antrean</span></a>
    <a class="tbl tbl-samar wa-aksi-atas" href="{{ route('panel.wa.pengaturan') }}">@include('panel._ikon', ['nama' => 'gear']) <span class="wa-lbl">Pengaturan</span></a>
@endsection

@section('isi')
<style>
    /* --- ramah HP: kepala tidak berdesakan, tombol cukup besar untuk jari --- */
    @media (max-width: 700px) {
        .kepala-aksi .tbl .wa-lbl { display: none !important; }
        .wa-aksi-atas .tbl { padding: .5rem .55rem; }
        .wa-variabel { gap: .55rem; }
        .wa-variabel button { padding: .5rem .8rem; font-size: .82rem; }
        .wa-baris { gap: .6rem; }
        .wa-baris .tbl { flex: 1 1 100%; justify-content: center; }
    }
</style>

@if (session('sukses'))
    <div class="kartu" style="border-left:4px solid var(--hijau);margin-bottom:1rem">
        <p style="margin:0;font-size:.88rem">{{ session('sukses') }}</p>
    </div>
@endif

<div class="kartu">
    <div class="kartu-kepala"><h3>Notifikasi otomatis</h3></div>
    <p style="margin:-.35rem 0 .6rem;font-size:.85rem;color:var(--tinta-muda);line-height:1.7">
        Pesan yang dibuat sistem saat ada kejadian di aplikasi. Bila aturannya
        <strong>“masuk antrean”</strong>, pengurus tinggal menekan tombol WhatsApp di halaman Antrean.
        Bila <strong>“kirim otomatis”</strong> dan gateway sudah diisi, pesannya langsung berangkat.
        @if (! $gatewaySiap)
            <br><em style="color:var(--kuning)">Gateway belum diisi, jadi semua notifikasi masuk antrean manual dulu.</em>
        @endif
    </p>
</div>

@foreach ($aturan as $a)
    <form class="kartu" method="post" action="{{ route('panel.wa.aturan.simpan') }}">
        @csrf
        <input type="hidden" name="kunci" value="{{ $a->kunci }}">
        <input type="hidden" name="nama" value="{{ $a->nama }}">

        <div class="kartu-kepala">
            <h3>{{ $a->nama }}
                @if ($a->aktif)
                    <span class="lencana lencana-hijau">aktif</span>
                @else
                    <span class="lencana">nonaktif</span>
                @endif
            </h3>
        </div>

        <div class="jaring-2">
            <div class="bidang lebar-penuh">
                <label for="isi-{{ $a->kunci }}">Isi pesan</label>
                <textarea id="isi-{{ $a->kunci }}" name="isi" rows="4" maxlength="2000">{{ $a->isi }}</textarea>
                <span class="bantuan">
                    Variabel tersedia:
                    @foreach ($variabel as $kunci => $contoh)
                        <code>{{ '{'.'{'.$kunci.'}'.'}' }}</code>
                    @endforeach
                    <code>{{ '{'.'{'.'nama'.'}'.'}' }}</code> <code>{{ '{'.'{'.'nama_santri'.'}'.'}' }}</code> <code>{{ '{'.'{'.'nama_donatur'.'}'.'}' }}</code> <code>{{ '{'.'{'.'nominal'.'}'.'}' }}</code>
                </span>
            </div>

            <div class="bidang">
                <label for="penerima-{{ $a->kunci }}">Cara kirim</label>
                <select id="penerima-{{ $a->kunci }}" name="penerima">
                    <option value="antrean" @selected($a->penerima === 'antrean')>Masuk antrean (pengurus menekan kirim)</option>
                    <option value="otomatis" @selected($a->penerima === 'otomatis')>Kirim otomatis lewat gateway</option>
                </select>
            </div>

            <div class="bidang">
                <label for="aktif-{{ $a->kunci }}">Status aturan</label>
                <select id="aktif-{{ $a->kunci }}" name="aktif">
                    <option value="1" @selected($a->aktif)>Aktif</option>
                    <option value="0" @selected(! $a->aktif)>Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="pemisah"></div>
        <button class="tbl tbl-utama" type="submit">Simpan aturan ini</button>
    </form>
@endforeach

@if ($aturan->isEmpty())
    <div class="kartu">
        <p class="kosong">Belum ada aturan. Jalankan pemasangan modul untuk membuat aturan bawaan.</p>
    </div>
@endif
@endsection
