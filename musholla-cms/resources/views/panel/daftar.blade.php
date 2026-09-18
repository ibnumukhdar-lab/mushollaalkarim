@extends('panel.layout')

@section('judul', $def['judul'])
@section('remah', 'Panel Pengelola › ' . $def['judul'])

@section('aksi')
    @if (empty($def['hanyaLihat']))
        <a class="tbl tbl-utama" href="{{ route('panel.tambah', $modul) }}">
            @include('panel._ikon', ['nama' => 'tambah']) Tambah
        </a>
    @endif
@endsection

@section('isi')
    <div class="kartu">
        <div class="kartu-kepala">
            <h3>{{ $def['judul'] }}</h3>
            <form class="cari-baris" method="get" action="{{ route('panel.daftar', $modul) }}">
                <input type="search" name="cari" value="{{ $cari }}" placeholder="Cari {{ strtolower($def['judul']) }}…" aria-label="Cari">
                <button class="tbl tbl-samar" type="submit">@include('panel._ikon', ['nama' => 'cari']) Cari</button>
                @if ($cari !== '')
                    <a class="tbl tbl-samar" href="{{ route('panel.daftar', $modul) }}">Bersihkan</a>
                @endif
            </form>
        </div>

        <p style="margin:-.35rem 0 .9rem;font-size:.84rem;color:var(--tinta-muda)">
            {{ $def['keterangan'] ?? '' }}
            @if ($baris->total() > 0) · <strong>{{ $baris->total() }}</strong> data @endif
        </p>

        @if ($baris->isEmpty())
            <p class="kosong">
                Belum ada data{{ $cari !== '' ? ' untuk pencarian "' . $cari . '"' : '' }}.
            </p>
        @else
            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                    <tr>
                        @foreach ($def['kolom'] as $k)
                            <th>{{ $k['label'] }}</th>
                        @endforeach
                        @if (empty($def['hanyaLihat']))
                            <th style="text-align:right">Aksi</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($baris as $r)
                        <tr>
                            @foreach ($def['kolom'] as $k)
                                @php
                                    [$teks, $kelas] = \App\Support\Panel::nilaiKolom($r, $k);
                                    $rataKanan = in_array($k['tipe'] ?? '', ['uang', 'angka'], true);
                                @endphp
                                <td data-label="{{ $k['label'] }}" @class(['angka' => $rataKanan])>
                                    @if ($kelas !== '')
                                        <span class="lencana {{ $kelas }}">{{ $teks }}</span>
                                    @else
                                        {{ $teks }}
                                    @endif
                                </td>
                            @endforeach

                            @if (empty($def['hanyaLihat']))
                                <td data-label="Aksi">
                                    <div class="aksi-baris">
                                        <a class="ikon-tbl" href="{{ route('panel.ubah', [$modul, $r->id]) }}" title="Ubah data" aria-label="Ubah">
                                            @include('panel._ikon', ['nama' => 'ubah'])
                                        </a>
                                        <form method="post" action="{{ route('panel.hapus', [$modul, $r->id]) }}"
                                              onsubmit="return confirm('Hapus data ini? Tindakan ini tidak bisa dibatalkan.')" style="margin:0">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ikon-tbl bahaya" type="submit" title="Hapus data" aria-label="Hapus">
                                                @include('panel._ikon', ['nama' => 'hapus'])
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if ($baris->hasPages())
                <div class="halaman">
                    @if ($baris->onFirstPage())
                        <span>‹</span>
                    @else
                        <a href="{{ $baris->previousPageUrl() }}" aria-label="Sebelumnya">‹</a>
                    @endif
                    <span class="aktif">{{ $baris->currentPage() }}</span>
                    <span>dari {{ $baris->lastPage() }}</span>
                    @if ($baris->hasMorePages())
                        <a href="{{ $baris->nextPageUrl() }}" aria-label="Berikutnya">›</a>
                    @else
                        <span>›</span>
                    @endif
                </div>
            @endif
        @endif
    </div>
@endsection
