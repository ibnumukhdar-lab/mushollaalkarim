@extends('panel.layout')

@php
    $baru = $rekaman === null;
    $judulForm = ($baru ? 'Tambah ' : 'Ubah ') . $def['judulSatu'];
@endphp

@section('judul', $judulForm)
@section('remah', 'Panel Pengelola › ' . $def['judul'] . ' › ' . ($baru ? 'Tambah' : 'Ubah'))

@section('aksi')
    <a class="tbl tbl-samar" href="{{ route('panel.daftar', $modul) }}">Kembali</a>
@endsection

@section('isi')
    <form class="kartu" method="post" action="{{ $baru ? route('panel.simpan', $modul) : route('panel.perbarui', [$modul, $rekaman->id]) }}"
          enctype="multipart/form-data">
        @csrf
        @if (! $baru) @method('PUT') @endif

        <div class="kartu-kepala">
            <h3>{{ $judulForm }}</h3>
        </div>
        <p style="margin:-.35rem 0 1rem;font-size:.84rem;color:var(--tinta-muda)">{{ $def['keterangan'] ?? '' }}</p>

        <div class="jaring-2">
            @foreach ($def['field'] as $f)
                @php
                    $nama = $f['nama'];
                    $nilaiLama = old($nama, $baru ? null : data_get($rekaman, $nama));
                    $lebar = ($f['lebar'] ?? '') === 'penuh' ? ' lebar-penuh' : '';
                @endphp

                @if ($f['tipe'] === 'saklar')
                    <div class="bidang saklar{{ $lebar }}">
                        <input type="hidden" name="{{ $nama }}" value="0">
                        <input type="checkbox" id="f-{{ $nama }}" name="{{ $nama }}" value="1" @checked((bool) $nilaiLama)>
                        <label for="f-{{ $nama }}">{{ $f['label'] }}</label>
                        @if (! empty($f['bantuan'])) <span class="bantuan">{{ $f['bantuan'] }}</span> @endif
                    </div>
                @else
                    <div class="bidang{{ $lebar }}">
                        <label for="f-{{ $nama }}">
                            {{ $f['label'] }}
                            @if (! empty($f['wajib'])) <span class="wajib">*</span> @endif
                        </label>

                        @switch($f['tipe'])
                            @case('teks-panjang')
                                <textarea id="f-{{ $nama }}" name="{{ $nama }}" rows="{{ $f['baris'] ?? 5 }}">{{ $nilaiLama }}</textarea>
                                @break

                            @case('pilihan')
                                <select id="f-{{ $nama }}" name="{{ $nama }}">
                                    <option value="">— pilih —</option>
                                    @foreach (($f['opsi'] ?? []) as $nilaiOpsi => $labelOpsi)
                                        <option value="{{ $nilaiOpsi }}" @selected((string) $nilaiLama === (string) $nilaiOpsi)>{{ $labelOpsi }}</option>
                                    @endforeach
                                </select>
                                @break

                            @case('angka')
                                <input type="number" id="f-{{ $nama }}" name="{{ $nama }}" value="{{ $nilaiLama }}" step="1">
                                @break

                            @case('uang')
                                <input type="number" id="f-{{ $nama }}" name="{{ $nama }}" value="{{ $nilaiLama }}" min="0" step="1000" placeholder="0">
                                @break

                            @case('tanggal')
                                <input type="date" id="f-{{ $nama }}" name="{{ $nama }}"
                                       value="{{ $nilaiLama ? \Illuminate\Support\Carbon::parse($nilaiLama)->format('Y-m-d') : '' }}">
                                @break

                            @case('tanggal-waktu')
                                <input type="datetime-local" id="f-{{ $nama }}" name="{{ $nama }}"
                                       value="{{ $nilaiLama ? \Illuminate\Support\Carbon::parse($nilaiLama)->format('Y-m-d\TH:i') : '' }}">
                                @break

                            @case('sandi')
                                <input type="password" id="f-{{ $nama }}" name="{{ $nama }}" value="" autocomplete="new-password">
                                @break

                            @case('berkas')
                                <input type="file" id="f-{{ $nama }}" name="{{ $nama }}">
                                @if (! $baru && $nilaiLama)
                                    <span class="lampiran-lama">
                                        @if (in_array(strtolower(pathinfo((string) $nilaiLama, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true))
                                            <img src="{{ url('/berkas/' . ltrim($nilaiLama, '/')) }}" alt="Lampiran saat ini">
                                        @else
                                            Lampiran saat ini:
                                        @endif
                                        <a href="{{ url('/berkas/' . ltrim($nilaiLama, '/')) }}" target="_blank" rel="noopener">buka berkas</a>
                                        — biarkan kosong bila tidak ingin mengganti.
                                    </span>
                                @endif
                                @break

                            @default
                                <input type="text" id="f-{{ $nama }}" name="{{ $nama }}" value="{{ $nilaiLama }}">
                        @endswitch

                        @if (! empty($f['bantuan'])) <span class="bantuan">{{ $f['bantuan'] }}</span> @endif
                    </div>
                @endif
            @endforeach
        </div>

        <div class="kaki-form">
            <button class="tbl tbl-utama" type="submit">{{ $baru ? 'Simpan' : 'Simpan perubahan' }}</button>
            <a class="tbl tbl-samar" href="{{ route('panel.daftar', $modul) }}">Batal</a>
        </div>
    </form>
@endsection
