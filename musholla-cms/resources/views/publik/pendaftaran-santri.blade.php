@extends('layouts.publik')

@section('judul', 'Pendaftaran Santri — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', 'Pendaftaran santri baru Majelis Tadris Al-Qur\'an Musholla Al Karim.')

@section('isi')
    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; Pendaftaran Santri</div>
    <h1 class="judul-halaman">Pendaftaran Santri Baru</h1>

    @if ($terkirim)
        <div class="pesan-sukses">
            <strong>Alhamdulillah, pendaftaran terkirim.</strong> Data Ananda sudah kami terima.
            Pengurus akan memeriksa dan menghubungi Anda melalui nomor WhatsApp yang dicantumkan.
        </div>
    @endif

    @if ($hal && trim(strip_tags(\App\Services\BersihkanTampilan::bersihkan($hal->isi))) !== '')
        <div class="kartu" style="margin-bottom:1.4rem">
            <div class="isi-halaman">{!! \App\Services\BersihkanTampilan::bersihkan($hal->isi) !!}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="pesan-galat">
            <strong>Ada yang perlu diperbaiki:</strong>
            <ul style="margin:.4rem 0 0 1.1rem">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form class="kartu form-kartu" method="post" action="/pendaftaran-santri">
        @csrf

        <h3 style="margin:.1rem 0 .8rem;color:var(--navy);font-size:1rem">Data Ananda</h3>
        <div class="jaring dua">
            <div class="baris">
                <label for="nama">Nama lengkap <span class="wajib">*</span></label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required maxlength="120">
            </div>
            <div class="baris">
                <label for="jenis_kelamin">Jenis kelamin <span class="wajib">*</span></label>
                <select id="jenis_kelamin" name="jenis_kelamin" required>
                    <option value="">— pilih —</option>
                    <option value="L" @selected(old('jenis_kelamin') === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('jenis_kelamin') === 'P')>Perempuan</option>
                </select>
            </div>
            <div class="baris">
                <label for="tempat_lahir">Tempat lahir</label>
                <input type="text" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir') }}" maxlength="80">
            </div>
            <div class="baris">
                <label for="tanggal_lahir">Tanggal lahir</label>
                <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}">
            </div>
            <div class="baris">
                <label for="kelas_sekolah">Kelas / sekolah</label>
                <input type="text" id="kelas_sekolah" name="kelas_sekolah" value="{{ old('kelas_sekolah') }}" maxlength="60" placeholder="mis. kelas 4 SD">
            </div>
        </div>

        <h3 style="margin:1.2rem 0 .8rem;color:var(--navy);font-size:1rem">Orang tua / wali</h3>
        <div class="jaring dua">
            <div class="baris">
                <label for="nama_ortu">Nama orang tua / wali <span class="wajib">*</span></label>
                <input type="text" id="nama_ortu" name="nama_ortu" value="{{ old('nama_ortu') }}" required maxlength="120">
            </div>
            <div class="baris">
                <label for="no_wa">Nomor WhatsApp <span class="wajib">*</span></label>
                <input type="tel" id="no_wa" name="no_wa" value="{{ old('no_wa') }}" required maxlength="25" placeholder="08xx">
            </div>
        </div>
        <div class="baris">
            <label for="catatan">Catatan untuk pengurus</label>
            <textarea id="catatan" name="catatan" rows="3" maxlength="1000" placeholder="mis. jadwal yang diinginkan, kondisi Ananda">{{ old('catatan') }}</textarea>
        </div>

        <button class="tombol-kirim" type="submit">Kirim pendaftaran</button>
    </form>
@endsection
