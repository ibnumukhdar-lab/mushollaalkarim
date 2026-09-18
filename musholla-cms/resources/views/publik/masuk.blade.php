@extends('layouts.publik')

@section('judul', 'Masuk — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', 'Masuk ke akun subscriber Musholla Al Karim.')

@section('isi')
    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; Masuk</div>
    <h1 class="judul-halaman">Masuk</h1>

    @if ($errors->any())
        <div class="pesan-galat">
            <strong>Ada yang perlu diperbaiki:</strong>
            <ul style="margin:.4rem 0 0 1.1rem">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="jaring dua">
        <form class="kartu form-kartu" method="post" action="/masuk">
            @csrf

            <div class="baris">
                <label for="email">Email <span class="wajib">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" autofocus>
            </div>

            <div class="baris">
                <label for="password">Sandi <span class="wajib">*</span></label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <div class="baris" style="display:flex;align-items:center;gap:.5rem">
                <input type="checkbox" id="ingat" name="ingat" value="1" style="width:auto">
                <label for="ingat" style="margin:0;font-weight:400">Ingat saya di perangkat ini</label>
            </div>

            <button type="submit" class="tombol-kirim">Masuk</button>
        </form>

        <div class="kartu">
            <h3>Belum punya akun?</h3>
            <p>Pendaftaran terbuka untuk siapa saja — satu pintu sebagai <strong>subscriber</strong>.</p>
            <p><a href="/daftar" class="tombol-kirim" style="display:inline-block;text-decoration:none">Daftar subscriber</a></p>
            <p style="color:var(--tinta-muda);font-size:.86rem">Lupa sandi? Sementara ini pengiriman surel belum aktif, jadi hubungi pengurus Musholla Al Karim untuk penyetelan ulang sandi.</p>
            <p style="color:var(--tinta-muda);font-size:.86rem">Akun pengurus/admin masuk lewat pintu yang sama, lalu otomatis diarahkan ke panel.</p>
        </div>
    </div>
@endsection
