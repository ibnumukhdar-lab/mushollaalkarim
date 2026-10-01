@extends('panel.layout')

@section('judul', 'Rincian Program')
@section('remah', 'Panel Pengelola › Konten Situs › Rincian Program')

@section('isi')
<style>
    .pr-kepala { display: flex; gap: .6rem; align-items: baseline; flex-wrap: wrap; }
    .pr-kepala h3 { margin: 0; }
    .pr-jenis { font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        padding: .16rem .5rem; border-radius: 999px; background: var(--hijau-muda); color: var(--hijau-tua); }
    .pr-jenis.infaq { background: #eaf3ff; color: #1d4d8a; }
    .pr-total { margin-left: auto; font-size: .92rem; font-weight: 700; color: var(--hijau-tua); font-variant-numeric: tabular-nums; }
    .pr-program { border: 1px solid var(--garis); border-radius: 12px; margin-bottom: .8rem; background: #fff; }
    .pr-program > summary {
        list-style: none; cursor: pointer; padding: .8rem .9rem; display: flex; gap: .6rem;
        align-items: center; flex-wrap: wrap;
    }
    .pr-program > summary::-webkit-details-marker { display: none; }
    .pr-program > summary::after { content: '▾'; color: var(--tinta-muda); font-size: .8rem; }
    .pr-program[open] > summary::after { content: '▴'; }
    .pr-program[open] > summary { border-bottom: 1px solid var(--garis); }
    .pr-isi { padding: .8rem .9rem 1rem; }
    /* Di HP: nama kebutuhan satu baris penuh, lalu jumlah | satuan | harga, lalu subtotal + tombol */
    .pr-baris { display: grid; gap: .4rem; align-items: center; padding: .55rem 0; border-bottom: 1px dashed var(--garis);
        grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .pr-baris:last-of-type { border-bottom: 0; }
    .pr-baris .nm { grid-column: 1 / -1; }
    .pr-baris .aksi { display: flex; gap: .3rem; align-items: center; justify-content: flex-end; grid-column: 1 / -1; }
    .pr-baris input { font: inherit; font-size: .86rem; padding: .38rem .5rem; border: 1px solid var(--garis);
        border-radius: 8px; width: 100%; background: #fff; }
    .pr-baris input.hp { text-align: right; font-variant-numeric: tabular-nums; }
    .pr-sub { flex: 1; min-width: 0; font-size: .74rem; color: var(--tinta-muda); }
    .pr-head { font-size: .68rem; letter-spacing: .05em; text-transform: uppercase; color: var(--tinta-muda); }
    .pr-tambah { margin-top: .9rem; padding-top: .8rem; border-top: 1px solid var(--garis); }
    .pr-tambah h4 { margin: 0 0 .5rem; font-size: .84rem; color: var(--hijau-tua); }
    .pr-kosong { font-size: .86rem; color: var(--tinta-muda); padding: .5rem 0; }
    .pr-catatan-atas { font-size: .84rem; color: var(--tinta-muda); line-height: 1.7; margin: -.3rem 0 .9rem; }
    @media (min-width: 760px) {
        .pr-baris { grid-template-columns: minmax(0, 1fr) 84px 84px 120px auto; }
        .pr-baris .nm, .pr-baris .aksi { grid-column: auto; }
        .pr-head { display: grid; grid-template-columns: minmax(0, 1fr) 84px 84px 120px auto; gap: .4rem; padding-bottom: .2rem; }
    }
    @media (max-width: 759px) { .pr-head { display: none; } }
</style>

@if (session('sukses'))
    <div class="kartu" style="border-left:4px solid var(--hijau);margin-bottom:1rem">
        <p style="margin:0;font-size:.88rem;color:var(--hijau-tua)">{{ session('sukses') }}</p>
    </div>
@endif
@if ($errors->any())
    <div class="kartu" style="border-left:4px solid var(--merah);margin-bottom:1rem">
        <p style="margin:0;font-size:.88rem;color:var(--merah)">{{ $errors->first() }}</p>
    </div>
@endif

<div class="kartu" style="margin-bottom:1rem">
    <div class="kartu-kepala"><h3>Rincian kebutuhan program</h3></div>
    <p class="pr-catatan-atas">
        Baris di bawah tampil sebagai <strong>tabel rincian</strong> di halaman program
        (<code>/wakaf/&lt;slug&gt;</code> atau <code>/infaq/&lt;slug&gt;</code>) dan di kartu Mari Berinfaq.
        Subtotal tiap baris &amp; totalnya dihitung aplikasi (jumlah × harga satuan), jadi totalnya
        selalu cocok dengan barisan di atasnya. Total rincian menggantikan angka Target pada program
        yang memilikinya. Nominal boleh ditulis “100.000” atau “Rp 100.000”.
    </p>
    <p class="pr-catatan-atas" style="margin-bottom:0">
        Program yang belum muncul? Tambahkan dulu di
        <a href="{{ route('panel.daftar', 'wakaf_program') }}">Wakaf &amp; Program Infaq</a>.
        Total seluruh rincian: <strong>Rp {{ number_format((float) $totalSemua, 0, ',', '.') }}</strong>.
    </p>
</div>

@forelse ($program as $p)
    @php $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); @endphp
    <details class="pr-program" @if ($loop->first) open @endif>
        <summary>
            <span class="pr-kepala" style="flex:1">
                <strong>{{ $p->nama }}</strong>
                <span class="pr-jenis {{ $p->jenis_infaq ? 'infaq' : '' }}">{{ $p->jenis_infaq ? 'infaq' : 'wakaf' }}</span>
                @unless ($p->aktif) <span class="lencana lencana-kuning">tidak tampil</span> @endunless
                <span style="font-size:.78rem;color:var(--tinta-muda)">{{ $p->rincian->count() }} baris</span>
            </span>
            <span class="pr-total">{{ $rp($p->total_rincian) }}</span>
        </summary>

        <div class="pr-isi">
            <p style="margin:0 0 .3rem;font-size:.8rem;color:var(--tinta-muda)">
                Halaman publik:
                @if ($p->slug)
                    <a href="{{ $p->tautan }}" target="_blank" rel="noopener">{{ str_replace(['https://', 'http://'], '', $p->tautan) }} ↗</a>
                @else
                    <em>belum ada slug — isi di formulir program</em>
                @endif
                @if (trim((string) $p->periode_label) !== '')
                    · {{ $p->periode_label }}
                @endif
            </p>

            <div class="pr-head">
                <span>Kebutuhan</span>
                <span style="text-align:right">Jumlah</span>
                <span>Satuan</span>
                <span style="text-align:right">Harga satuan</span>
                <span></span>
            </div>

            @forelse ($p->rincian as $b)
                <form class="pr-baris" method="post" action="{{ route('panel.rincian.baris', $p->id) }}">
                    @csrf
                    <input type="hidden" name="baris_id" value="{{ $b->id }}">
                    <input class="nm" type="text" name="nama" value="{{ $b->nama }}" maxlength="200" required aria-label="Nama kebutuhan">
                    <input class="hp" type="text" inputmode="decimal" name="jumlah" value="{{ $b->ringkas_jumlah ? rtrim(rtrim(number_format((float) $b->jumlah, 2, ',', '.'), '0'), ',') : '1' }}" aria-label="Jumlah">
                    <input type="text" name="satuan" value="{{ $b->satuan }}" maxlength="30" placeholder="bulan/kali/unit" aria-label="Satuan">
                    <input class="hp" type="text" inputmode="numeric" name="harga_satuan" value="{{ number_format((float) $b->harga_satuan, 0, ',', '.') }}" aria-label="Harga satuan">
                    <span class="aksi">
                        <span class="pr-sub">Subtotal {{ $rp($b->subtotal) }}{{ $b->catatan ? ' · '.$b->catatan : '' }}</span>
                        <button class="ikon-tbl" type="submit" name="aksi" value="simpan" title="Simpan perubahan baris ini" aria-label="Simpan baris">
                            @include('panel._ikon', ['nama' => 'ubah'])
                        </button>
                        <button class="ikon-tbl bahaya" type="submit" name="aksi" value="hapus" formnovalidate
                                title="Hapus baris ini" aria-label="Hapus baris"
                                onclick="return confirm('Hapus baris “{{ $b->nama }}”?')">
                            @include('panel._ikon', ['nama' => 'hapus'])
                        </button>
                    </span>
                </form>
            @empty
                <p class="pr-kosong">Belum ada rincian. Tambahkan baris pertama di bawah.</p>
            @endforelse

            <form class="pr-tambah" method="post" action="{{ route('panel.rincian.baris', $p->id) }}">
                @csrf
                <h4>Tambah baris</h4>
                <div class="pr-baris">
                    <input class="nm" type="text" name="nama" maxlength="200" placeholder="mis. Tagihan listrik musholla" required aria-label="Nama kebutuhan baru">
                    <input class="hp" type="text" inputmode="decimal" name="jumlah" value="1" aria-label="Jumlah">
                    <input type="text" name="satuan" maxlength="30" placeholder="bulan" aria-label="Satuan">
                    <input class="hp" type="text" inputmode="numeric" name="harga_satuan" placeholder="100.000" aria-label="Harga satuan">
                    <span class="aksi">
                        <button class="ikon-tbl" type="submit" name="aksi" value="simpan" title="Tambah baris" aria-label="Tambah baris">
                            @include('panel._ikon', ['nama' => 'tambah'])
                        </button>
                    </span>
                </div>
                <div style="margin-top:.35rem">
                    <input type="text" name="catatan" maxlength="255" placeholder="Catatan kecil (opsional), mis. dibayar tiap tanggal 20" aria-label="Catatan baru">
                </div>
            </form>
        </div>
    </details>
@empty
    <div class="kartu">
        <p class="pr-kosong">Belum ada program donasi. Tambahkan dulu di
            <a href="{{ route('panel.daftar', 'wakaf_program') }}">Wakaf &amp; Program Infaq</a>.</p>
    </div>
@endforelse
@endsection
