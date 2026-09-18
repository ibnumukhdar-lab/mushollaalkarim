@extends('panel.layout')

@section('judul', 'Pengaturan Situs')
@section('remah', 'Panel Pengelola › Pengaturan Situs')

@section('isi')
    <form class="kartu" method="post" action="{{ route('panel.pengaturan.simpan') }}">
        @csrf

        <div class="kartu-kepala">
            <h3>Identitas & kontak musholla</h3>
        </div>
        <p style="margin:-.35rem 0 1rem;font-size:.84rem;color:var(--tinta-muda)">
            Isian ini muncul di halaman publik (kepala, kaki situs, halaman infaq, dan laporan).
        </p>

        <div class="jaring-2">
            @foreach ($baku as $kunci => $info)
                <div class="bidang{{ in_array($kunci, ['alamat', 'rekening'], true) ? ' lebar-penuh' : '' }}">
                    <label for="p-{{ $kunci }}">{{ $info['label'] }}</label>
                    @if (in_array($kunci, ['alamat', 'rekening'], true))
                        <textarea id="p-{{ $kunci }}" name="isi[{{ $kunci }}]" rows="3">{{ $tersimpan[$kunci] ?? '' }}</textarea>
                    @else
                        <input type="text" id="p-{{ $kunci }}" name="isi[{{ $kunci }}]" value="{{ $tersimpan[$kunci] ?? '' }}">
                    @endif
                    @if (! empty($info['bantuan'])) <span class="bantuan">{{ $info['bantuan'] }}</span> @endif
                </div>
            @endforeach
        </div>

        @if (! empty($tambahan))
            <div class="pemisah"></div>
            <div class="kartu-kepala">
                <h3>Isian tambahan</h3>
            </div>
            <div class="jaring-2">
                @foreach ($tambahan as $kunci => $nilai)
                    <div class="bidang">
                        <label for="p-{{ $kunci }}">{{ $kunci }}</label>
                        <input type="text" id="p-{{ $kunci }}" name="isi[{{ $kunci }}]" value="{{ $nilai }}">
                    </div>
                @endforeach
            </div>
        @endif

        <div class="pemisah"></div>
        <div class="kartu-kepala">
            <h3>Tambah isian baru</h3>
        </div>
        <div class="jaring-2">
            <div class="bidang">
                <label for="baru_kunci">Nama kunci</label>
                <input type="text" id="baru_kunci" name="baru_kunci" placeholder="mis. kontak_wa">
                <span class="bantuan">Huruf kecil, pakai garis bawah bila perlu.</span>
            </div>
            <div class="bidang">
                <label for="baru_nilai">Nilai</label>
                <input type="text" id="baru_nilai" name="baru_nilai" placeholder="Isi nilai">
            </div>
        </div>

        <div class="kaki-form">
            <button class="tbl tbl-utama" type="submit">Simpan pengaturan</button>
            <a class="tbl tbl-samar" href="{{ route('panel.dasbor') }}">Batal</a>
        </div>
    </form>
@endsection
