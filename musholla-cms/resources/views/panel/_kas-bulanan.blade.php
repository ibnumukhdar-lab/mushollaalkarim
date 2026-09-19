{{--
    Rekap kas bulanan + tutup kas — hanya tampil di halaman panel "Kas Musholla".
    Data: App\Support\KasBulanan::untukTampilan() (saldo bersambung tiap bulan).
--}}
@php
    $bulanIni = $kasBulanan['berjalan'];
    $riwayat = array_reverse(array_values(array_filter($kasBulanan['baris'], fn ($b) => $b['jenis'] === 'bulan')));
    $rupiah = fn ($angka) => 'Rp ' . number_format((float) $angka, 0, ',', '.');
@endphp

@push('gaya')
    <style>
        .kas-ringkas { display: grid; gap: .8rem; grid-template-columns: 1fr; }
        @media (min-width: 700px) { .kas-ringkas { grid-template-columns: repeat(4, 1fr); } }
        .kas-sel { background: #fff; border: 1px solid var(--garis); border-radius: 12px; padding: .8rem .9rem; }
        .kas-sel .lbl { display: block; font-size: .7rem; letter-spacing: .07em; text-transform: uppercase; color: var(--tinta-muda); }
        .kas-sel .n { display: block; font-size: 1.08rem; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--hijau-tua); }
        .kas-sel .n.masuk { color: #1f6b41; }
        .kas-sel .n.keluar { color: var(--merah); }
        .kas-sel.menonjol { background: linear-gradient(160deg, #356a4e, #2b5740); border-color: #2b5740; }
        .kas-sel.menonjol .lbl, .kas-sel.menonjol .n { color: #fff; }
        .kas-sel.menonjol .kecil { color: rgba(255,255,255,.85); font-size: .74rem; }

        .kas-aksi-bulan { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; margin-left: auto; }
        .kas-aksi-bulan details { display: inline-block; }
        .kas-aksi-bulan summary {
            list-style: none; cursor: pointer; font-size: .78rem; color: var(--tinta-muda);
            text-decoration: underline; text-underline-offset: 2px;
        }
        .kas-aksi-bulan summary::-webkit-details-marker { display: none; }
        .kas-aksi-bulan input[type=text] {
            font: inherit; font-size: .84rem; padding: .4rem .6rem; border: 1px solid var(--garis);
            border-radius: 9px; margin-top: .4rem; width: min(100%, 22rem);
        }

        .tabel-bulan { width: 100%; border-collapse: collapse; }
        .tabel-bulan th {
            text-align: left; font-size: .7rem; letter-spacing: .06em; text-transform: uppercase;
            color: var(--tinta-muda); font-weight: 600; padding: .5rem .55rem; border-bottom: 1px solid var(--garis);
        }
        .tabel-bulan td { padding: .58rem .55rem; border-bottom: 1px solid var(--garis); font-size: .88rem; vertical-align: middle; }
        .tabel-bulan tr:last-child td { border-bottom: 0; }
        .tabel-bulan td.angka { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .tabel-bulan td.angka.masuk { color: #1f6b41; }
        .tabel-bulan td.angka.keluar { color: var(--merah); }
        .tabel-bulan tr.bulan-ini { background: #f7fbf8; }
        .tabel-bulan tr.lewat td { color: var(--tinta-muda); }
        .lencana-kas { font-size: .7rem; border-radius: 999px; padding: .12rem .5rem; border: 1px solid var(--hijau-garis); background: var(--hijau-muda); color: var(--hijau-tua); white-space: nowrap; justify-self: end; }
        .lencana-kas.abu { border-color: var(--garis); background: #f4f7f5; color: var(--tinta-muda); }
        .lencana-kas.kuning { border-color: #eedfb4; background: #f8f1dd; color: var(--kuning); }
        .kas-catatan-baris { display: block; font-size: .74rem; color: var(--tinta-muda); margin-top: .15rem; }
    </style>
@endpush

<div class="kartu" style="margin-bottom:1.1rem">
    <div class="kartu-kepala">
        <h3>Kas Bulanan</h3>
        <span style="font-size:.8rem;color:var(--tinta-muda);margin-right:auto">
            {{ $bulanIni['label'] }}{{ $bulanIni['ditutup'] ? ' · sudah ditutup' : ' · bulan berjalan' }}
        </span>
        @unless ($bulanIni['ditutup'])
            <form class="kas-aksi-bulan" method="post" action="{{ route('panel.kas.tutup') }}"
                  onsubmit="return confirm('Tutup kas {{ $bulanIni['label'] }}? Angka bulan ini dibekukan dan sisa saldonya menjadi saldo awal bulan berikutnya.')">
                @csrf
                <input type="hidden" name="periode" value="{{ $bulanIni['periode'] }}">
                <button class="tbl tbl-utama" type="submit">
                    @include('panel._ikon', ['nama' => 'kas']) Tutup kas {{ $bulanIni['label_pendek'] }}
                </button>
                <details>
                    <summary>+ catatan penutupan</summary>
                    <input type="text" name="catatan" maxlength="500" placeholder="mis. sisa kas diserahkan ke bendahara">
                </details>
            </form>
        @endunless
    </div>

    <p style="margin:-.35rem 0 .9rem;font-size:.84rem;color:var(--tinta-muda)">
        Sisa saldo tiap bulan otomatis menjadi saldo awal bulan berikutnya, jadi rekapnya bersambung.
        Bulan yang sudah ditutup angkanya dibekukan supaya laporan lama tidak berubah.
    </p>

    <div class="kas-ringkas" style="margin-bottom:1.1rem">
        <div class="kas-sel">
            <span class="lbl">Saldo awal bulan ini</span>
            <span class="n">{{ $rupiah($bulanIni['saldo_awal']) }}</span>
        </div>
        <div class="kas-sel">
            <span class="lbl">Masuk bulan ini</span>
            <span class="n masuk">+ {{ $rupiah($bulanIni['masuk']) }}</span>
        </div>
        <div class="kas-sel">
            <span class="lbl">Keluar bulan ini</span>
            <span class="n keluar">− {{ $rupiah($bulanIni['keluar']) }}</span>
        </div>
        <div class="kas-sel menonjol">
            <span class="lbl">{{ $bulanIni['ditutup'] ? 'Saldo akhir (ditutup)' : 'Saldo berjalan' }}</span>
            <span class="n">{{ $rupiah($bulanIni['saldo_akhir']) }}</span>
            @if ($bulanIni['penutup'])
                <span class="kecil">ditutup oleh {{ $bulanIni['penutup'] }}</span>
            @endif
        </div>
    </div>

    <div class="tabel-bungkus">
        <table class="tabel tabel-bulan">
            <thead>
            <tr>
                <th>Bulan</th>
                <th style="text-align:right">Saldo awal</th>
                <th style="text-align:right">Masuk</th>
                <th style="text-align:right">Keluar</th>
                <th style="text-align:right">Saldo akhir</th>
                <th>Keadaan</th>
                <th style="text-align:right">Tindakan</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($riwayat as $b)
                <tr @class(['bulan-ini' => $b['berjalan']])>
                    <td data-label="Bulan">
                        {{ $b['label'] }}
                        @if ($b['catatan'])
                            <span class="kas-catatan-baris">{{ \Illuminate\Support\Str::limit($b['catatan'], 70) }}</span>
                        @endif
                    </td>
                    <td data-label="Saldo awal" class="angka">{{ $rupiah($b['saldo_awal']) }}</td>
                    <td data-label="Masuk" class="angka masuk">{{ $b['masuk'] > 0 ? '+ ' . $rupiah($b['masuk']) : '—' }}</td>
                    <td data-label="Keluar" class="angka keluar">{{ $b['keluar'] > 0 ? '− ' . $rupiah($b['keluar']) : '—' }}</td>
                    <td data-label="Saldo akhir" class="angka"><strong>{{ $rupiah($b['saldo_akhir']) }}</strong></td>
                    <td data-label="Keadaan">
                        @if (! $b['ditutup'] && $b['berjalan'])
                            <span class="lencana-kas abu">Bergulir</span>
                        @elseif (! $b['ditutup'])
                            <span class="lencana-kas abu">Terbuka</span>
                        @elseif ($b['selisih'] != 0)
                            <span class="lencana-kas kuning">Catatan berubah</span>
                        @else
                            <span class="lencana-kas">Tertutup</span>
                        @endif
                    </td>
                    <td data-label="Tindakan">
                        <div class="aksi-baris">
                            @if (! $b['ditutup'] && ($b['masuk'] > 0 || $b['keluar'] > 0))
                                <form method="post" action="{{ route('panel.kas.tutup') }}" style="margin:0"
                                      onsubmit="return confirm('Tutup kas {{ $b['label'] }} sekarang?')">
                                    @csrf
                                    <input type="hidden" name="periode" value="{{ $b['periode'] }}">
                                    <button class="ikon-tbl" type="submit" title="Tutup kas {{ $b['label'] }}" aria-label="Tutup kas">
                                        @include('panel._ikon', ['nama' => 'kunci'])
                                    </button>
                                </form>
                            @endif

                            @if ($b['ditutup'] && $b['selisih'] != 0)
                                <form method="post" action="{{ route('panel.kas.hitungUlang') }}" style="margin:0"
                                      onsubmit="return confirm('Hitung ulang angka {{ $b['label'] }} dari catatan kas terbaru?')">
                                    @csrf
                                    <input type="hidden" name="periode" value="{{ $b['periode'] }}">
                                    <button class="ikon-tbl" type="submit" title="Catatan berubah — hitung ulang" aria-label="Hitung ulang" style="color:var(--kuning)">
                                        @include('panel._ikon', ['nama' => 'putar'])
                                    </button>
                                </form>
                            @endif

                            @if ($b['ditutup'])
                                <form method="post" action="{{ route('panel.kas.buka') }}" style="margin:0"
                                      onsubmit="return confirm('Buka kembali kas {{ $b['label'] }}? Angkanya akan kembali mengikuti catatan kas.')">
                                    @csrf
                                    <input type="hidden" name="periode" value="{{ $b['periode'] }}">
                                    <button class="ikon-tbl" type="submit" title="Buka kembali" aria-label="Buka kembali">
                                        @include('panel._ikon', ['nama' => 'buka'])
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
