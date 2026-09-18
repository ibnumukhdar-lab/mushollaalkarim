@extends('panel.layout')

@php
    $baru = $rekaman === null;
    $judulForm = ($baru ? 'Tambah ' : 'Ubah ') . $def['judulSatu'];
    $adaBagian = collect($def['field'])->contains(fn ($f) => ! empty($f['bagian']));
    $bagianTerbuka = false;
    $bagianSekarang = null;
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

        @foreach ($def['field'] as $f)
            @php
                $nama = $f['nama'];
                $nilaiLama = old($nama, $baru ? null : data_get($rekaman, $nama));
                $lebar = ($f['lebar'] ?? '') === 'penuh' ? ' lebar-penuh' : '';
                $bagian = $f['bagian'] ?? null;
            @endphp

            {{-- pembuka bagian (bila formulir memakai pengelompokan) --}}
            @if ($bagian && $bagian !== $bagianSekarang)
                @if ($bagianTerbuka)
                    </div>
                @endif
                <div class="pemisah"></div>
                <h3 class="form-bagian">{{ $bagian }}</h3>
                <div class="jaring-2">
                @php
                    $bagianTerbuka = true;
                    $bagianSekarang = $bagian;
                @endphp
            @elseif (! $bagian && ! $bagianTerbuka)
                <div class="jaring-2">
                @php $bagianTerbuka = true; @endphp
            @endif

            @if ($f['tipe'] === 'saklar')
                <div class="bidang saklar{{ $lebar }}">
                    <input type="hidden" name="{{ $nama }}" value="0">
                    <input type="checkbox" id="f-{{ $nama }}" name="{{ $nama }}" value="1" @checked((bool) $nilaiLama)>
                    <label for="f-{{ $nama }}">{{ $f['label'] }}</label>
                    @if (! empty($f['bantuan'])) <span class="bantuan">{{ $f['bantuan'] }}</span> @endif
                </div>
            @elseif ($f['tipe'] === 'berkas')
                <div class="{{ trim($lebar) }}">
                    @include('partials.potong-gambar', [
                        'nama' => $nama,
                        'label' => $f['label'],
                        'nilai' => $baru ? null : data_get($rekaman, $nama),
                    ])
                    @if (! empty($f['bantuan'])) <span class="bantuan" style="display:block;margin-top:-.5rem">{{ $f['bantuan'] }}</span> @endif
                </div>
            @elseif ($f['tipe'] === 'pilihan-banyak')
                @php
                    $sumber = $f['sumber'] ?? null;
                    $pilihan = $sumber ? $sumber::query()->orderBy('nama')->get() : collect();
                    if (old($nama) !== null) {
                        $terpilih = (array) old($nama);
                    } elseif ($baru || empty($f['relasi'])) {
                        $terpilih = [];
                    } else {
                        $terpilih = $rekaman->{$f['relasi']}()->pluck('kategori.id')->all();
                    }
                @endphp
                <div class="bidang lebar-penuh">
                    <label>{{ $f['label'] }}</label>
                    <div class="pilihan-banyak">
                        @forelse ($pilihan as $p)
                            <label class="kotak-centang">
                                <input type="checkbox" name="{{ $nama }}[]" value="{{ $p->id }}" @checked(in_array($p->id, $terpilih))>
                                <span>{{ $p->nama }}</span>
                            </label>
                        @empty
                            <span class="bantuan">Belum ada pilihan. Tambahkan lebih dulu di menu Kategori.</span>
                        @endforelse
                    </div>
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
                            @if (! empty($f['editor']))
                                <div class="editor-alat" data-editor-alat data-sasaran="f-{{ $nama }}">
                                    <button type="button" data-awalan="## " title="Jadikan sub-judul">Sub-judul</button>
                                    <button type="button" data-bungkus="**" title="Tebal">Tebal</button>
                                    <button type="button" data-bungkus="*" title="Miring">Miring</button>
                                    <button type="button" data-awalan="- " data-baris="1" title="Jadikan daftar">Daftar</button>
                                    <button type="button" data-awalan="&gt; " data-baris="1" title="Jadikan kutipan">Kutipan</button>
                                    <button type="button" data-tautan="1" title="Sisipkan tautan">Tautan</button>
                                </div>
                            @endif
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

                        @default
                            <input type="text" id="f-{{ $nama }}" name="{{ $nama }}" value="{{ $nilaiLama }}">
                    @endswitch

                    @if (! empty($f['bantuan'])) <span class="bantuan">{{ $f['bantuan'] }}</span> @endif
                </div>
            @endif
        @endforeach

        @if ($bagianTerbuka)
            </div>
        @endif

        <div class="kaki-form">
            <button class="tbl tbl-utama" type="submit">{{ $baru ? 'Simpan' : 'Simpan perubahan' }}</button>
            <a class="tbl tbl-samar" href="{{ route('panel.daftar', $modul) }}">Batal</a>
        </div>
    </form>
@endsection

@push('skrip')
    <script>
        // Perkakas penulisan: menyisipkan PENANDA teks (bukan tag HTML),
        // sebab WAF hosting menolakPOST multipart yang memuat tag HTML.
        // Penanda dirapikan menjadi HTML oleh App\Support\Tulis saat tampil.
        document.querySelectorAll('[data-editor-alat]').forEach(function (alat) {
            var sasaran = document.getElementById(alat.getAttribute('data-sasaran'));
            if (!sasaran) return;

            function seleksi(awal, akhir) {
                sasaran.focus();
                sasaran.selectionStart = awal;
                sasaran.selectionEnd = akhir;
            }

            function bungkus(penanda) {
                var awal = sasaran.selectionStart, akhir = sasaran.selectionEnd;
                var isi = sasaran.value.substring(awal, akhir);
                sasaran.value = sasaran.value.substring(0, awal) + penanda + isi + penanda + sasaran.value.substring(akhir);
                seleksi(awal + penanda.length, awal + penanda.length + isi.length);
            }

            function awalan(teks, perBaris) {
                var nilai = sasaran.value;
                var awal = sasaran.selectionStart, akhir = sasaran.selectionEnd;
                var mulai = nilai.lastIndexOf('\n', awal - 1) + 1;
                var selesai = nilai.indexOf('\n', akhir);
                if (selesai === -1) selesai = nilai.length;
                var blok = nilai.substring(mulai, selesai);

                var hasil;
                if (perBaris) {
                    hasil = blok.split('\n').map(function (b) {
                        return b.trim() === '' ? b : teks + b;
                    }).join('\n');
                } else {
                    hasil = teks + blok;
                }

                sasaran.value = nilai.substring(0, mulai) + hasil + nilai.substring(selesai);
                seleksi(mulai, mulai + hasil.length);
            }

            alat.querySelectorAll('[data-bungkus]').forEach(function (t) {
                t.addEventListener('click', function () { bungkus(t.getAttribute('data-bungkus')); });
            });

            alat.querySelectorAll('[data-awalan]').forEach(function (t) {
                t.addEventListener('click', function () {
                    var perBaris = t.getAttribute('data-baris') === '1';
                    awalan(t.getAttribute('data-awalan'), perBaris);
                });
            });

            var tt = alat.querySelector('[data-tautan]');
            if (tt) tt.addEventListener('click', function () {
                var awal = sasaran.selectionStart, akhir = sasaran.selectionEnd;
                var isi = sasaran.value.substring(awal, akhir) || 'teks tautan';
                var alamat = window.prompt('Alamat tautan (https://...)', 'https://');
                if (!alamat) return;
                var hasil = '[' + isi + '](' + alamat + ')';
                sasaran.value = sasaran.value.substring(0, awal) + hasil + sasaran.value.substring(akhir);
                seleksi(awal + hasil.length, awal + hasil.length);
            });
        });
    </script>
@endpush
