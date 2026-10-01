@extends('panel.layout')

@section('judul', 'Pusat WhatsApp')
@section('remah', 'Panel Pengelola › Pusat WhatsApp › Kirim Pesan')

@section('aksi')
    <a class="tbl tbl-samar" href="{{ route('panel.wa.antrean') }}">
        @include('panel._ikon', ['nama' => 'kabar']) <span class="wa-lbl">Antrean</span>
        @if ($ringkasan['menunggu'] > 0)
            <span class="lencana lencana-kuning">{{ $ringkasan['menunggu'] }}</span>
        @endif
    </a>
    <a class="tbl tbl-samar" href="{{ route('panel.wa.pusat', ['laporan' => 1, 'grup' => 'donatur']) }}">
        @include('panel._ikon', ['nama' => 'kas']) <span class="wa-lbl">Laporan kas</span>
    </a>
    <a class="tbl tbl-samar" href="{{ route('panel.wa.pengaturan') }}">@include('panel._ikon', ['nama' => 'gear']) <span class="wa-lbl">Pengaturan</span></a>
@endsection

@section('isi')
<style>
    .wa-grup { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .wa-grup a {
        font-size: .84rem; font-weight: 600; padding: .42rem .8rem; border-radius: 99px;
        border: 1px solid var(--hijau-garis); background: #fff; color: var(--hijau-tua); text-decoration: none;
        display: inline-flex; gap: .4rem; align-items: center;
    }
    .wa-grup a.hidup { background: var(--hijau); border-color: var(--hijau); color: #fff; }
    .wa-grup a span { font-size: .76rem; opacity: .8; }
    .wa-daftar { max-height: 320px; overflow-y: auto; border: 1px solid var(--garis); border-radius: 12px; }
    .wa-orang { display: flex; align-items: center; gap: .6rem; padding: .55rem .8rem; border-bottom: 1px solid var(--garis); font-size: .88rem; }
    .wa-orang:last-child { border-bottom: 0; }
    .wa-orang:hover { background: var(--hijau-muda); }
    .wa-orang .nm { font-weight: 600; color: var(--tinta); }
    .wa-orang .no { margin-left: auto; color: var(--tinta-muda); font-size: .8rem; font-variant-numeric: tabular-nums; }
    .wa-orang input { width: 17px; height: 17px; accent-color: var(--hijau); }
    .wa-variabel { display: flex; flex-wrap: wrap; gap: .4rem; margin: .5rem 0 .8rem; }
    .wa-variabel button {
        font: inherit; font-size: .78rem; padding: .3rem .6rem; border-radius: 8px; cursor: pointer;
        border: 1px solid var(--hijau-garis); background: #fff; color: var(--hijau-tua);
    }
    .wa-variabel button:hover { background: var(--hijau-muda); }
    .wa-baris { display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem; align-items: center; }
    .wa-pilihan { display: flex; gap: 1rem; flex-wrap: wrap; }
    .wa-pilihan label { display: flex; gap: .4rem; align-items: center; font-size: .88rem; }
    .wa-pratinjau {
        margin-top: .9rem; border: 1px solid var(--hijau-garis); background: #f4faf6; border-radius: 12px;
        padding: .85rem 1rem; font-size: .88rem; line-height: 1.7; white-space: pre-line; color: #2b3f30;
    }
    .wa-catatan { font-size: .82rem; color: var(--tinta-muda); margin-top: .7rem; line-height: 1.65; }
    .wa-panduan { margin: .8rem 0 .4rem; border: 1px solid var(--hijau-garis); border-radius: 11px; padding: .55rem .8rem; background: #fbfdfc; }
    .wa-panduan summary { font-size: .84rem; font-weight: 600; color: var(--hijau-tua); cursor: pointer; }
    .wa-peringatan { font-size: .82rem; color: var(--kuning); margin: .5rem 0 0; line-height: 1.6; }
    @media (max-width: 640px) {
        .wa-orang .no { display: none; }
        .wa-grup a { font-size: .8rem; padding: .4rem .7rem; }
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

    /* --- kartu Laporan Kas Bulanan (satu bulan bisa dipilih) --- */
    .wa-laporan .wa-bulan { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; margin-bottom: .85rem; }
    .wa-laporan .wa-bulan label { font-size: .78rem; color: var(--tinta-muda); }
    .wa-laporan .wa-bulan select {
        font: inherit; font-size: .88rem; padding: .42rem .6rem; border: 1px solid var(--garis);
        border-radius: 9px; background: #fff; color: var(--tinta); max-width: 100%;
    }
    .wa-angka { display: grid; gap: .5rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 700px) { .wa-angka { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .wa-angka > div { border: 1px solid var(--garis); border-radius: 11px; padding: .55rem .7rem; background: #fff; }
    .wa-angka span { display: block; font-size: .68rem; letter-spacing: .06em; text-transform: uppercase; color: var(--tinta-muda); }
    .wa-angka b { font-size: 1rem; font-variant-numeric: tabular-nums; color: var(--tinta); }
    .wa-angka b.hijau { color: #1f6b41; }
    .wa-angka b.merah { color: var(--merah); }
    .wa-angka .menonjol { background: linear-gradient(160deg, #356a4e, #2b5740); border-color: #2b5740; }
    .wa-angka .menonjol span { color: rgba(255,255,255,.85); }
    .wa-angka .menonjol b { color: #fff; }
</style>

@if (session('galat'))
    <div class="kartu" style="border-left:4px solid var(--merah);margin-bottom:1rem">
        <p style="margin:0;font-size:.88rem;color:var(--merah)">{{ session('galat') }}</p>
    </div>
@endif

<form method="post" action="{{ route('panel.wa.kirim') }}" id="wa-form">
    @csrf

    {{-- 0 · laporan kas satu bulan (dibuka dari halaman Kas) --}}
    @if ($bulanLaporan)
        @php
            $lr = $bulanLaporan['ringkas'];
            $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        @endphp
        <input type="hidden" name="periode" value="{{ $bulanLaporan['periode'] }}">

        <div class="kartu wa-laporan">
            <div class="kartu-kepala">
                <h3>Laporan Kas Bulanan</h3>
                <span class="lencana {{ $lr['ditutup'] ? '' : 'lencana-kuning' }}">
                    {{ $lr['ditutup'] ? 'kas sudah ditutup' : 'kas masih berjalan' }}
                </span>
            </div>

            <div class="wa-bulan">
                <label for="wa-periode">Periode yang dilaporkan</label>
                <select id="wa-periode" onchange="waGantiBulan(this.value)">
                    @foreach ($daftarBulan as $db)
                        <option value="{{ $db['periode'] }}" @selected($db['periode'] === $bulanLaporan['periode'])>
                            {{ $db['label'] }}{{ $db['ditutup'] ? ' · sudah ditutup' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="wa-angka">
                <div><span>Saldo awal</span><b>{{ $rp($lr['saldo_awal']) }}</b></div>
                <div><span>Pemasukan</span><b class="hijau">+ {{ $rp($lr['masuk']) }}</b></div>
                <div><span>Pengeluaran</span><b class="merah">− {{ $rp($lr['keluar']) }}</b></div>
                <div class="menonjol"><span>Saldo akhir</span><b>{{ $rp($lr['saldo_akhir']) }}</b></div>
            </div>

            <p class="wa-catatan">
                Pesan di bawah memakai angka <strong>{{ $bulanLaporan['label'] }}</strong> —
                bukan angka bulan ini. Yang dipakai: {{ $lr['ditutup'] ? 'angka yang dibekukan saat kas ditutup' : 'catatan kas bulan itu apa adanya' }}.
                Penerima sudah disaring ke kelompok <strong>Donatur</strong>; centang yang perlu.
            </p>
        </div>
    @endif

    {{-- 1 · penerima --}}
    <div class="kartu">
        <div class="kartu-kepala">
            <h3>1 · Pilih penerima</h3>
            <span style="font-size:.82rem;color:var(--tinta-muda)">
                <a href="#" onclick="waPilih(true);return false">pilih semua</a> ·
                <a href="#" onclick="waPilih(false);return false">kosongkan</a>
            </span>
        </div>

        <div class="wa-grup">
            @foreach (['subscriber' => 'Subscribers', 'donatur' => 'Donatur', 'admin' => 'Admin'] as $kunci => $label)
                <a href="{{ route('panel.wa.pusat', array_filter(['grup' => $kunci, 'laporan' => $bulanLaporan ? 1 : null, 'periode' => $bulanLaporan['periode'] ?? null])) }}" @class(['hidup' => $grupAktif === $kunci])>
                    {{ $label }} <span>{{ $semua[$kunci]->count() }}</span>
                </a>
            @endforeach
        </div>

        @if ($penerima->isEmpty())
            <p class="kosong">Belum ada penerima di kelompok ini. Nomor WhatsApp bisa diisi di
                <a href="{{ route('panel.daftar', 'donatur') }}">Donatur</a> atau
                <a href="{{ route('panel.daftar', 'ustadz') }}">Ustadz</a>.</p>
        @else
            <div class="wa-daftar">
                @foreach ($penerima as $orang)
                    <label class="wa-orang">
                        <input type="checkbox" class="wa-centang" name="pilih[]" checked
                               value="{{ $orang['nomor'] }}|{{ $orang['nama'] }}">
                        <span class="nm">{{ $orang['nama'] }}</span>
                        @if (! empty($orang['data']['santri'])) <span style="font-size:.78rem;color:var(--tinta-muda)">· {{ $orang['data']['santri'] }}</span> @endif
                        <span class="no">{{ $orang['nomor'] }}</span>
                    </label>
                @endforeach
            </div>
        @endif
        <p class="wa-catatan" id="wa-hitung">{{ $penerima->count() }} penerima siap dipilih.</p>
    </div>

    {{-- 2 · pesan --}}
    <div class="kartu">
        <div class="kartu-kepala">
            <h3>2 · Tulis pesan</h3>
        </div>

        <div class="bidang">
            <label for="wa-judul">Nama kampanye (untuk riwayat)</label>
            <input type="text" id="wa-judul" name="judul" maxlength="150" value="{{ $judulAwal }}" placeholder="mis. Laporan Kas September">
        </div>

        @if ($template->isNotEmpty())
            <div class="bidang">
                <label for="wa-template">Ambil dari template</label>
                <select id="wa-template">
                    <option value="">— tulis sendiri —</option>
                    @foreach ($template as $t)
                        <option value="{{ $t->id }}" data-isi="{{ $t->isi }}">{{ $t->judul }}</option>
                    @endforeach
                </select>
                <span class="bantuan">Template bisa ditambah/diubah di
                    <a href="{{ route('panel.daftar', 'wa_template') }}">Template Pesan</a>.</span>
            </div>
        @endif

        <div class="bidang lebar-penuh">
            <label for="wa-isi">Isi pesan</label>
            @if ($bulanLaporan)
                <span class="bantuan" style="margin:0 0 .35rem">
                    Draf laporan <strong>{{ $bulanLaporan['label'] }}</strong> sudah disiapkan:
                    pemasukan, pengeluaran, saldo awal &amp; saldo akhir memakai angka bulan itu (bukan bulan ini).
                    Silakan disunting dulu bila perlu.
                </span>
            @endif
            <textarea id="wa-isi" name="isi" rows="10" required>{{ $isiAwal }}</textarea>
            <span class="bantuan">Variabel akan diganti otomatis sesuai data tiap penerima.</span>
        </div>

        <details class="wa-panduan">
            <summary>Panduan variabel — klik untuk melihat daftar isian otomatis</summary>
            <p class="wa-catatan" style="margin:.2rem 0 .6rem">
                Klik salah satu untuk menyisipkannya ke pesan. Tiap penerima menerima nilai miliknya sendiri.
            </p>
            <div class="wa-variabel">
                @foreach ($variabel as $kunci => $contoh)
                    <button type="button" onclick="waSisip('{{ $kunci }}')" title="Contoh: {{ $contoh }}">{{ '{'.'{'.$kunci.'}'.'}' }}</button>
                @endforeach
                @foreach (['nama' => 'Nama penerima', 'nama_donatur' => 'Nama donatur', 'nama_ortu' => 'Nama orang tua', 'nama_santri' => 'Nama santri', 'nominal' => 'Nominal infaq', 'tujuan' => 'Tujuan infaq', 'judul_berita' => 'Judul tulisan', 'ringkasan_berita' => 'Ringkasan tulisan', 'tautan_berita' => 'Tautan tulisan'] as $kunci => $label)
                    <button type="button" onclick="waSisip('{{ $kunci }}')" title="{{ $label }}">{{ '{'.'{'.$kunci.'}'.'}' }}</button>
                @endforeach
            </div>
        </details>
        <p class="wa-peringatan" id="wa-peringatan" style="display:none"></p>

        <div class="wa-baris">
            <button class="tbl tbl-samar" type="button" onclick="waPratinjau()">Pratinjau</button>
            <span style="font-size:.82rem;color:var(--tinta-muda)" id="wa-info-pratinjau"></span>
        </div>
        <div class="wa-pratinjau" id="wa-pratinjau" style="display:none"></div>
    </div>

    {{-- 3 · cara kirim --}}
    <div class="kartu">
        <div class="kartu-kepala">
            <h3>3 · Cara kirim</h3>
        </div>

        <div class="wa-pilihan">
            <label>
                <input type="radio" name="mode" value="manual" checked>
                <span><strong>Manual</strong> — gratis, tanpa langganan. Aplikasi menyiapkan tautan WhatsApp tiap orang,
                pengurus tinggal menekan kirim lalu menandai.</span>
            </label>
            <label>
                <input type="radio" name="mode" value="gateway" @disabled(! $gatewaySiap)>
                <span><strong>Otomatis lewat gateway</strong> — terkirim sendiri dari antrean.
                @if (! $gatewaySiap)
                    <em style="color:var(--kuning)">Belum siap: URL &amp; token diisi di Pengaturan.</em>
                @else
                    <em style="color:var(--hijau-tua)">Gateway siap.</em>
                @endif
                </span>
            </label>
        </div>

        <input type="hidden" name="grup" value="{{ $grupAktif }}">
        <div class="wa-baris">
            <button class="tbl tbl-utama" type="submit" id="wa-kirim">
                <span class="wa-lbl">Kirim pesan</span><span class="wa-lbl-hp">Kirim</span>
            </button>
            <a class="tbl tbl-samar" href="{{ route('panel.daftar', 'wa_broadcast') }}">Riwayat kampanye</a>
        </div>
        <p class="wa-catatan">
            Pesan tidak pernah terkirim diam-diam: apa pun modenya, semuanya tercatat di
            <strong>Antrean</strong> — jelas siapa yang sudah dan belum menerima.
        </p>
    </div>
</form>

<script>
    function waPilih(nilai) {
        document.querySelectorAll('.wa-centang').forEach(c => c.checked = nilai);
        waHitung();
    }
    function waHitung() {
        const n = document.querySelectorAll('.wa-centang:checked').length;
        const t = document.getElementById('wa-hitung');
        if (t) t.textContent = n + ' penerima dipilih.';
        const k = document.getElementById('wa-kirim');
        if (k) k.style.opacity = n ? '1' : '.5';
    }
    function waSisip(kunci) {
        const el = document.getElementById('wa-isi');
        const teks = ['{', '{', kunci, '}', '}'].join('');
        const awal = el.selectionStart ?? el.value.length;
        el.value = el.value.slice(0, awal) + teks + el.value.slice(el.selectionEnd ?? awal);
        el.focus();
        el.selectionStart = el.selectionEnd = awal + teks.length;
    }
    async function waPratinjau() {
        const isi = document.getElementById('wa-isi').value;
        const grup = '{{ $grupAktif }}';
        const periode = '{{ $bulanLaporan['periode'] ?? '' }}';
        const kotak = document.getElementById('wa-pratinjau');
        const info = document.getElementById('wa-info-pratinjau');
        if (!isi.trim()) { info.textContent = 'Isi pesan masih kosong.'; return; }
        kotak.style.display = 'block';
        kotak.textContent = 'Menyiapkan…';
        try {
            const r = await fetch('{{ route('panel.wa.pratinjau') }}?isi=' + encodeURIComponent(isi) + '&grup=' + grup + '&periode=' + periode);
            const d = await r.json();
            kotak.textContent = d.pesan;
            info.textContent = d.penerima ? ('Contoh untuk: ' + d.penerima) : '';
            waPeriksaVariabel(d.tidak_dikenal || []);
        } catch (e) {
            kotak.textContent = 'Gagal menyiapkan pratinjau.';
        }
    }
    function waGantiBulan(periode) {
        if (!periode) return;
        location.href = '{{ route('panel.wa.pusat') }}?laporan=1&grup={{ $grupAktif }}&periode=' + periode;
    }
    function waPeriksaVariabel(daftar) {
        const el = document.getElementById('wa-peringatan');
        if (!el) return;
        if (daftar.length) {
            el.textContent = 'Perhatian: variabel berikut belum dikenal → ' + daftar.map(v => '{{' + v + '}}').join(', ')
                + '. Periksa penulisannya, atau pakai tombol di Panduan variabel.';
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    }
    document.querySelectorAll('.wa-centang').forEach(c => c.addEventListener('change', waHitung));
    document.getElementById('wa-template')?.addEventListener('change', function () {
        const opsi = this.options[this.selectedIndex];
        if (opsi.dataset.isi) { document.getElementById('wa-isi').value = opsi.dataset.isi; }
    });
    waHitung();
</script>
@endsection
