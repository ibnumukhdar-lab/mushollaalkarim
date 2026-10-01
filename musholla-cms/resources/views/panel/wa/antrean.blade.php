@extends('panel.layout')

@section('judul', 'Antrean WhatsApp')
@section('remah', 'Panel Pengelola › Pusat WhatsApp › Antrean & Riwayat')

@section('aksi')
    <a class="tbl tbl-utama" href="{{ route('panel.wa.pusat') }}">@include('panel._ikon', ['nama' => 'tambah']) <span class="wa-lbl">Kirim pesan</span></a>
    <a class="tbl tbl-samar" href="{{ route('panel.wa.pengaturan') }}">@include('panel._ikon', ['nama' => 'gear']) <span class="wa-lbl">Pengaturan</span></a>
@endsection

@section('isi')
<style>
    .wa-angka { display: grid; grid-template-columns: repeat(3, 1fr); gap: .8rem; margin-bottom: 1rem; }
    .wa-angka div { border-radius: 12px; padding: .85rem 1rem; color: #fff; }
    .wa-angka small { font-size: .72rem; text-transform: uppercase; letter-spacing: .6px; opacity: .9; display: block; }
    .wa-angka b { font-size: 1.4rem; }
    .wa-a { background: linear-gradient(160deg, #a8811f, #c39a2c); }
    .wa-b { background: linear-gradient(160deg, #2f6046, #3f7d5c); }
    .wa-c { background: linear-gradient(160deg, #8c3a41, #b1484f); }
    .wa-tabel { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .wa-tabel th { text-align: left; font-size: .72rem; letter-spacing: .5px; text-transform: uppercase;
        color: var(--tinta-muda); padding: .55rem .6rem; border-bottom: 1px solid var(--garis); }
    .wa-tabel td { padding: .55rem .6rem; border-bottom: 1px solid var(--garis); vertical-align: top; }
    .wa-tabel tr:last-child td { border-bottom: 0; }
    .wa-tabel .no { color: var(--tinta-muda); font-size: .8rem; font-variant-numeric: tabular-nums; }
    .wa-lencana { font-size: .72rem; font-weight: 700; padding: .2rem .6rem; border-radius: 99px; white-space: nowrap; }
    .wa-hijau { background: #e2f3e9; color: #1d6b45; }
    .wa-kuning { background: #fdf1d8; color: #8a6412; }
    .wa-merah { background: #fbe4e6; color: #a4373f; }
    .wa-aksi { display: flex; gap: .35rem; flex-wrap: wrap; }
    .wa-aksi a, .wa-aksi button {
        font: inherit; font-size: .78rem; padding: .3rem .6rem; border-radius: 8px; cursor: pointer;
        border: 1px solid var(--hijau-garis); background: #fff; color: var(--hijau-tua); text-decoration: none;
    }
    .wa-aksi .utama { background: #2f9e68; border-color: #2f9e68; color: #fff; }
    .wa-kampanye { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .wa-kampanye a {
        font-size: .82rem; padding: .4rem .75rem; border-radius: 9px; text-decoration: none;
        border: 1px solid var(--hijau-garis); background: #fff; color: var(--hijau-tua);
    }
    .wa-kampanye a.hidup { background: var(--hijau); border-color: var(--hijau); color: #fff; }
    .wa-pesan-isi { font-size: .78rem; color: var(--tinta-muda); line-height: 1.55; margin-top: .2rem; white-space: pre-line; }
    @media (max-width: 700px) {
        .wa-angka { grid-template-columns: 1fr; }
        .wa-tabel thead { display: none; }
        .wa-tabel tr { display: block; border: 1px solid var(--garis); border-radius: 11px; padding: .6rem .7rem; margin-bottom: .55rem; }
        .wa-tabel td { display: flex; justify-content: space-between; gap: .8rem; border: 0; padding: .22rem 0; }
        .wa-tabel td::before { content: attr(data-l); font-size: .74rem; color: var(--tinta-muda); }
    }

    /* --- ramah HP: kepala tidak berdesakan, tombol cukup besar untuk jari --- */
    @media (max-width: 700px) {
        .kepala-aksi .tbl .wa-lbl { display: none !important; }
        .wa-aksi-atas .tbl { padding: .5rem .55rem; }
        .wa-variabel { gap: .55rem; }
        .wa-variabel button { padding: .5rem .8rem; font-size: .82rem; }
        .wa-baris { gap: .6rem; }
        .wa-baris .tbl { flex: 1 1 100%; justify-content: center; }
    }
</style>

@if (session('sukses'))
    <div class="kartu" style="border-left:4px solid var(--hijau);margin-bottom:1rem">
        <p style="margin:0;font-size:.88rem">{{ session('sukses') }}</p>
    </div>
@endif

<div class="wa-angka">
    <div class="wa-a"><small>Menunggu dikirim</small><b>{{ $ringkasan['menunggu'] }}</b></div>
    <div class="wa-b"><small>Sudah terkirim</small><b>{{ $ringkasan['terkirim'] }}</b></div>
    <div class="wa-c"><small>Gagal</small><b>{{ $ringkasan['gagal'] }}</b></div>
</div>

@if ($kampanye->isNotEmpty())
    <div class="wa-kampanye">
        @foreach ($kampanye as $k)
            <a href="{{ route('panel.wa.antrean', ['k' => $k->id]) }}" @class(['hidup' => $aktif && $aktif->id === $k->id])>
                {{ \Illuminate\Support\Str::limit($k->judul ?: 'Kampanye #'.$k->id, 34) }}
            </a>
        @endforeach
    </div>
@endif

@if ($aktif)
    <div class="kartu">
        <div class="kartu-kepala">
            <h3>{{ $aktif->judul ?: 'Kampanye #'.$aktif->id }}</h3>
            <span style="font-size:.82rem;color:var(--tinta-muda)">
                {{ $aktif->mode === 'gateway' ? 'otomatis' : 'manual' }} ·
                {{ $aktif->mulai_at?->translatedFormat('j M Y H:i') }}
            </span>
        </div>

        @if ($kemajuan)
            <p style="margin:-.35rem 0 .9rem;font-size:.85rem;color:var(--tinta-muda)">
                {{ $kemajuan['terkirim'] }} dari {{ $kemajuan['total'] }} terkirim
                @if ($kemajuan['menunggu']) · {{ $kemajuan['menunggu'] }} menunggu @endif
                @if ($kemajuan['gagal']) · {{ $kemajuan['gagal'] }} gagal @endif
            </p>
        @endif

        @if ($gatewaySiap && $aktif->pesan()->whereIn('status', ['menunggu', 'gagal'])->exists())
            <form method="post" action="{{ route('panel.wa.proses', $aktif->id) }}" style="margin-bottom:.9rem">
                @csrf
                <button class="tbl tbl-samar" type="submit">Kirim sisanya lewat gateway</button>
            </form>
        @endif

        <div class="tabel-bungkus">
            <table class="wa-tabel">
                <thead>
                    <tr>
                        <th>Penerima</th>
                        <th>Status</th>
                        <th>Pesan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pesan as $p)
                        @php [$label, $warna] = $p->status_lencana; @endphp
                        <tr>
                            <td data-l="Penerima">
                                <strong>{{ $p->nama ?: 'Tanpa nama' }}</strong>
                                <span class="no"> · {{ $p->nomor }}</span>
                                @if ($p->dikirim_at) <span class="no"> · {{ $p->dikirim_at->translatedFormat('j M H:i') }}</span> @endif
                            </td>
                            <td data-l="Status"><span class="wa-lencana wa-{{ $warna }}">{{ $label }}</span></td>
                            <td data-l="Pesan">
                                <div class="wa-pesan-isi">{{ \Illuminate\Support\Str::limit($p->pesan, 150) }}</div>
                                @if ($p->galat) <div style="font-size:.76rem;color:var(--merah);margin-top:.2rem">{{ $p->galat }}</div> @endif
                            </td>
                            <td data-l="Aksi">
                                <div class="wa-aksi">
                                    <a class="utama" href="{{ $p->tautan }}" target="_blank" rel="noopener">Buka WhatsApp ↗</a>
                                    @if ($p->status !== 'terkirim')
                                        <form method="post" action="{{ route('panel.wa.tandai', $p->id) }}" style="display:inline">
                                            @csrf
                                            <button type="submit">Tandai terkirim</button>
                                        </form>
                                    @endif
                                    @if ($p->status === 'menunggu')
                                        <form method="post" action="{{ route('panel.wa.lewati', $p->id) }}" style="display:inline">
                                            @csrf
                                            <button type="submit">Lewati</button>
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
@else
    <div class="kartu">
        <p class="kosong">Belum ada kampanye. Mulai dari <a href="{{ route('panel.wa.pusat') }}">Kirim pesan</a>.</p>
    </div>
@endif

@if ($otomatis->isNotEmpty())
    <div class="kartu">
        <div class="kartu-kepala">
            <h3>Notifikasi otomatis</h3>
            <a class="tbl tbl-samar" href="{{ route('panel.wa.aturan') }}">Atur aturan</a>
        </div>
        <p style="margin:-.35rem 0 .9rem;font-size:.85rem;color:var(--tinta-muda)">
            Pesan yang dibuat sistem dari kejadian di aplikasi (infaq diverifikasi, pendaftaran santri, dsb.).
        </p>

        <div class="tabel-bungkus">
            <table class="wa-tabel">
                <thead>
                    <tr><th>Untuk</th><th>Status</th><th>Pesan</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($otomatis as $p)
                        @php [$label, $warna] = $p->status_lencana; @endphp
                        <tr>
                            <td data-l="Untuk"><strong>{{ $p->nama }}</strong> <span class="no">· {{ $p->nomor }}</span></td>
                            <td data-l="Status"><span class="wa-lencana wa-{{ $warna }}">{{ $label }}</span></td>
                            <td data-l="Pesan"><div class="wa-pesan-isi">{{ \Illuminate\Support\Str::limit($p->pesan, 140) }}</div></td>
                            <td data-l="Aksi">
                                <div class="wa-aksi">
                                    <a class="utama" href="{{ $p->tautan }}" target="_blank" rel="noopener">Buka WhatsApp ↗</a>
                                    @if ($p->status !== 'terkirim')
                                        <form method="post" action="{{ route('panel.wa.tandai', $p->id) }}" style="display:inline">
                                            @csrf
                                            <button type="submit">Tandai terkirim</button>
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
@endif
@endsection
