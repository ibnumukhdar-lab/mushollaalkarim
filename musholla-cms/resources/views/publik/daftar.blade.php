@extends('layouts.publik')

@section('judul', 'Daftar Subscriber — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', 'Satu pintu pendaftaran anggota (subscriber) Musholla Al Karim: ikut kegiatan, dapat kabar kajian, dan info program musholla.')

@section('isi')
    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; Daftar</div>
    <h1 class="judul-halaman">Daftar Subscriber</h1>

    @if ($errors->any())
        <div class="pesan-galat">
            <strong>Ada yang perlu diperbaiki:</strong>
            <ul style="margin:.4rem 0 0 1.1rem">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="jaring dua">
        <form class="kartu form-kartu" method="post" action="/daftar">
            @csrf

            <div class="baris">
                <label for="nama_lengkap">Nama lengkap <span class="wajib">*</span></label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap') }}" required maxlength="120" autocomplete="name">
            </div>

            <div class="baris">
                <label for="email">Email <span class="wajib">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email">
                <small>Dipakai untuk masuk kembali ke akun Anda.</small>
            </div>

            <div class="baris">
                <label for="no_wa">Nomor WhatsApp</label>
                <input type="tel" id="no_wa" name="no_wa" value="{{ old('no_wa') }}" maxlength="25" placeholder="mis. 0812xxxxxxx">
                <small>Opsional — untuk kabar kajian dan kegiatan.</small>
            </div>

            <div class="jaring dua">
                <div class="baris">
                    <label for="password">Sandi <span class="wajib">*</span></label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                    <small>Minimal 8 huruf.</small>
                </div>
                <div class="baris">
                    <label for="password_confirmation">Ulangi sandi <span class="wajib">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="tombol-kirim">Daftar sekarang</button>
        </form>

        <div class="kartu">
            <h3>Satu pintu keanggotaan</h3>
            <p>Cukup satu pendaftaran untuk semua kegiatan Musholla Al Karim. Setelah terdaftar, Anda menjadi <strong>subscriber</strong>:</p>
            <ul style="padding-left:1.1rem;font-size:.9rem;color:var(--tinta-muda)">
                <li>mendapat kabar kajian &amp; program musholla,</li>
                <li>tercatat sebagai donatur/anggota yang aktif,</li>
                <li>dapat mengikuti infaq &amp; laporan kas terbuka.</li>
            </ul>
            <p style="margin-top:1rem">Sudah punya akun? <a href="/masuk">Masuk di sini</a>.</p>
        </div>
    </div>
@endsection
