@extends('layouts.publik')

@section('judul', 'Ubah Profil — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', 'Perbarui nama dan nomor WhatsApp akun Anda.')

@php
    $namaTampil = $anggota->nama_lengkap ?: $anggota->name;
@endphp

@section('isi')
    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; <a href="/anggota">Akun Saya</a> &nbsp;›&nbsp; Ubah Profil</div>
    <h1 class="judul-halaman">Ubah Profil</h1>

    @if ($errors->any())
        <div class="pesan-galat">
            <strong>Ada yang perlu diperbaiki:</strong>
            <ul style="margin:.4rem 0 0 1.1rem">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="jaring dua">
        <form class="kartu form-kartu" method="post" action="/anggota/profil">
            @csrf

            <h3 style="margin:.1rem 0 .8rem;color:var(--hijau-tua);font-size:1rem">Data anggota</h3>

            <div class="baris">
                <label for="nama_lengkap">Nama lengkap <span class="wajib">*</span></label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $namaTampil) }}" required minlength="3" maxlength="120" autocomplete="name">
            </div>

            <div class="baris">
                <label for="no_wa">Nomor WhatsApp</label>
                <input type="tel" id="no_wa" name="no_wa" value="{{ old('no_wa', $anggota->no_wa) }}" maxlength="25" placeholder="mis. 0812xxxxxxx" autocomplete="tel">
                <span class="bantuan">Dipakai pengurus untuk kabar kajian, kegiatan, dan konfirmasi infaq.</span>
            </div>

            <div class="baris">
                <label for="email_tampil">Email</label>
                <input type="email" id="email_tampil" value="{{ $anggota->email }}" readonly disabled>
                <span class="bantuan">Email dipakai untuk masuk dan tidak bisa diubah sendiri. Hubungi pengurus bila perlu diganti.</span>
            </div>

            <div style="border-top:1px solid var(--garis);margin:1.1rem 0 .9rem"></div>

            <h3 style="margin:0 0 .3rem;color:var(--hijau-tua);font-size:1rem">Ganti sandi <span style="font-weight:400;color:var(--tinta-muda);font-size:.82rem">(opsional)</span></h3>
            <p style="margin:0 0 .9rem;font-size:.84rem;color:var(--tinta-muda)">Biarkan kosong bila tidak ingin mengganti sandi.</p>

            <div class="baris">
                <label for="sandi_lama">Sandi sekarang</label>
                <input type="password" id="sandi_lama" name="sandi_lama" autocomplete="current-password">
            </div>

            <div class="jaring dua">
                <div class="baris">
                    <label for="sandi_baru">Sandi baru</label>
                    <input type="password" id="sandi_baru" name="sandi_baru" minlength="8" autocomplete="new-password">
                </div>
                <div class="baris">
                    <label for="sandi_baru_confirmation">Ulangi sandi baru</label>
                    <input type="password" id="sandi_baru_confirmation" name="sandi_baru_confirmation" minlength="8" autocomplete="new-password">
                </div>
            </div>

            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.4rem">
                <button type="submit" class="tombol-kirim">Simpan perubahan</button>
                <a href="/anggota" class="tombol-kirim" style="background:#fff;color:var(--hijau-tua);border:1px solid var(--garis);text-decoration:none">Batal</a>
            </div>
        </form>

        <div class="kartu">
            <h3>Kenapa nomor WhatsApp penting?</h3>
            <p>Pengurus memakai nomor Anda untuk mengirim kabar kajian &amp; kegiatan, mengingatkan jadwal program, dan mengonfirmasi infaq yang disalurkan lewat musholla.</p>
            <p style="color:var(--tinta-muda);font-size:.86rem">Nomor tidak pernah ditampilkan di halaman publik — hanya pengurus yang melihatnya di panel pengelola.</p>
        </div>
    </div>
@endsection
