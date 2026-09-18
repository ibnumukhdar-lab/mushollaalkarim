@extends('layouts.publik')

@section('judul', 'Akun Saya — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', 'Halaman anggota (subscriber) Musholla Al Karim.')

@section('isi')
    @php
        $namaTampil = $anggota->nama_lengkap ?: $anggota->name;
    @endphp

    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; Akun Saya</div>

    @if (session('baru_daftar'))
        <div class="pesan-sukses">
            <strong>Alhamdulillah, pendaftaran berhasil.</strong>
            Anda sudah terdaftar sebagai subscriber Musholla Al Karim.
        </div>
    @endif

    @if (session('profil_tersimpan'))
        <div class="pesan-sukses">
            <strong>Perubahan tersimpan.</strong>
            @if (session('profil_tersimpan') === 'sandi')
                Nama, nomor WhatsApp, dan sandi Anda sudah diperbarui.
            @else
                Nama dan nomor WhatsApp Anda sudah diperbarui.
            @endif
        </div>
    @endif

    <div class="pahlawan">
        <h1>Assalamu'alaikum, {{ $namaTampil }}</h1>
        <p>Anda terdaftar sebagai <strong>subscriber</strong> Musholla Al Karim.</p>
        <div class="aksi">
            <a href="/mari-berinfaq" class="tombol">Infaq sekarang</a>
            <a href="/laporan-kas" class="tombol">Lihat laporan kas</a>
            <a href="/berita" class="tombol garis">Kabar musholla</a>
        </div>
    </div>

    <div class="jaring dua">
        <div class="kartu">
            <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem">
                <h3 style="margin:0;margin-right:auto">Data akun</h3>
                <a href="/anggota/profil" class="tombol-kecil">Ubah profil</a>
            </div>
            <table style="width:100%;font-size:.9rem;border-collapse:collapse">
                <tr><td style="padding:.3rem 0;color:var(--tinta-muda)">Nama</td><td>{{ $namaTampil }}</td></tr>
                <tr><td style="padding:.3rem 0;color:var(--tinta-muda)">Email</td><td>{{ $anggota->email }}</td></tr>
                <tr><td style="padding:.3rem 0;color:var(--tinta-muda)">WhatsApp</td><td>{{ $anggota->no_wa ?: '—' }}</td></tr>
                <tr><td style="padding:.3rem 0;color:var(--tinta-muda)">Peran</td><td>{{ $anggota->peran }}</td></tr>
                <tr><td style="padding:.3rem 0;color:var(--tinta-muda)">Terdaftar</td><td>{{ $anggota->created_at?->format('d/m/Y') }}</td></tr>
            </table>
            @if ($anggota->punyaPeran('admin'))
                <p style="margin-top:.9rem"><a href="/kelola">Buka panel pengelola &rarr;</a></p>
            @endif
        </div>

        <div class="kartu">
            <h3>Yang bisa Anda lakukan</h3>
            <ul style="padding-left:1.1rem;font-size:.9rem;color:var(--tinta-muda)">
                <li>mengikuti kabar kajian &amp; program musholla,</li>
                <li>menyalurkan infaq dan melihat laporan kas terbuka,</li>
                <li>menghubungi pengurus bila ada perubahan data.</li>
            </ul>
            <form method="post" action="/keluar" style="margin-top:1rem">
                @csrf
                <button type="submit" class="tombol-kirim" style="background:#5b6b7c">Keluar dari akun</button>
            </form>
        </div>
    </div>
@endsection
