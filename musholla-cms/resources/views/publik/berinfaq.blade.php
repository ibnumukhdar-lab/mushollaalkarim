@extends('layouts.publik')

@section('judul', 'Mari Berinfaq — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', 'Salurkan infaq, sedekah, dan wakaf Anda untuk kemakmuran Musholla Al Karim — rekening & QRIS resmi.')

@php
    $waPengurus = preg_replace('/\D/', '', (string) ($pengaturan['kontak_wa'] ?? ''));
    $pesanWa = rawurlencode("Assalamu'alaikum, saya sudah menyalurkan infaq ke Musholla Al Karim. Berikut bukti transfernya.");
@endphp

@section('isi')
    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; Mari Berinfaq</div>

    @if ($terkirim)
        <div class="pesan-sukses">
            <strong>Terima kasih!</strong> Catatan infaq Anda sudah kami terima dan akan diperiksa pengurus.
            Bila perlu konfirmasi, pengurus akan menghubungi nomor WhatsApp yang Anda cantumkan.
            @if ($waPengurus !== '')
                <div style="margin-top:.7rem">
                    <a class="tombol-kirim" href="https://wa.me/{{ $waPengurus }}?text={{ $pesanWa }}" target="_blank" rel="noopener"
                       style="display:inline-block;text-decoration:none">Kirim bukti lewat WhatsApp</a>
                </div>
            @endif
        </div>
    @endif

    {{-- Ajakan utama: tombol infaq langsung di atas, tanpa perlu menggulir --}}
    <section class="pahlawan">
        <h1>Mari Berinfaq</h1>
        <p>Setiap infaq Anda menjadi cahaya bagi musholla: listrik, kebersihan, kegiatan kajian, dan bantuan umat.</p>
        <div class="aksi">
            <button type="button" class="tombol" onclick="bukaInfaq()">Infaq sekarang</button>
            <a class="tombol garis" href="#konfirmasi">Sudah transfer? Konfirmasi</a>
        </div>
    </section>

    <h2 class="bagian">Cara menyalurkan</h2>
    <div class="jaring dua" style="margin-bottom:1.4rem">
        @include('publik._bayar')
    </div>

    <h2 class="bagian">Ringkasan Infaq</h2>
    <div class="jaring dua" style="margin-bottom:1.4rem">
        <div class="kartu">
            <div class="tanggal">Terkumpul &amp; terverifikasi</div>
            <h3 style="font-size:1.35rem;margin:.2rem 0 0">Rp {{ number_format($totalTerverifikasi, 0, ',', '.') }}</h3>
            <p>Dari {{ number_format($jumlahDonatur, 0, ',', '.') }} donatur. Setiap catatan yang masuk diperiksa pengurus
                sebelum dicatat — lalu tampil terbuka di <a href="/laporan-kas">laporan keuangan</a>.</p>
        </div>
        <div class="kartu">
            <div class="tanggal">Alur singkat</div>
            <p style="margin-top:.5rem">
                <strong>1.</strong> Salurkan lewat rekening atau QRIS di atas.<br>
                <strong>2.</strong> Isi formulir konfirmasi di bawah, sertakan bukti transfer bila ada.<br>
                <strong>3.</strong> Pengurus memverifikasi, lalu catatannya muncul di laporan keuangan.
            </p>
        </div>
    </div>

    @if ($hal && trim(strip_tags(\App\Services\BersihkanTampilan::bersihkan($hal->isi))) !== '')
        <div class="kartu" style="margin-bottom:1.4rem">
            <div class="isi-halaman">{!! \App\Services\BersihkanTampilan::bersihkan($hal->isi) !!}</div>
        </div>
    @endif

    @if ($wakaf->isNotEmpty())
        <h2 class="bagian">Program yang Bisa Dibantu</h2>
        <div class="jaring dua" style="margin-bottom:1.4rem">
            @foreach ($wakaf as $w)
                <div class="kartu">
                    <h3>{{ $w->nama }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $w->keterangan), 200) }}</p>
                    @if ($w->target > 0)
                        @php $persen = min(100, (int) round(((float) $w->terkumpul / max(1, (float) $w->target)) * 100)); @endphp
                        <div style="height:8px;border-radius:6px;background:var(--hijau-muda);margin:.7rem 0 .4rem;overflow:hidden">
                            <span style="display:block;height:100%;width:{{ $persen }}%;background:var(--hijau-lembut)"></span>
                        </div>
                        <p style="margin:0">Terkumpul <strong>Rp {{ number_format((float) $w->terkumpul, 0, ',', '.') }}</strong>
                            dari Rp {{ number_format((float) $w->target, 0, ',', '.') }} ({{ $persen }}%)</p>
                    @endif
                    <p style="margin-top:.6rem">
                        <button type="button" class="tombol-kecil" onclick="bukaInfaq()">Infaq untuk program ini</button>
                    </p>
                </div>
            @endforeach
        </div>
    @endif

    <h2 class="bagian" id="konfirmasi">Konfirmasi Infaq Anda</h2>
    <p style="font-size:.86rem;color:var(--tinta-muda);margin:-.4rem 0 1rem">
        Isi setelah menyalurkan, supaya pengurus bisa mencocokkan dengan mutasi rekening — catatan ini juga yang membuat
        infaq Anda tampil di laporan keuangan.
    </p>

    @if ($errors->any())
        <div class="pesan-galat">
            <strong>Ada yang perlu diperbaiki:</strong>
            <ul style="margin:.4rem 0 0 1.1rem">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form class="kartu form-kartu" method="post" action="/mari-berinfaq" enctype="multipart/form-data">
        @csrf
        <div class="jaring dua">
            <div class="baris">
                <label for="nama_donatur">Nama Anda <span class="wajib">*</span></label>
                <input type="text" id="nama_donatur" name="nama_donatur" value="{{ old('nama_donatur') }}" required maxlength="120"
                       placeholder="Boleh “Hamba Allah” bila tanpa nama">
            </div>
            <div class="baris">
                <label for="no_wa">Nomor WhatsApp <span class="wajib">*</span></label>
                <input type="tel" id="no_wa" name="no_wa" value="{{ old('no_wa') }}" required maxlength="25" placeholder="08xx">
                <small>Untuk konfirmasi balik dari pengurus.</small>
            </div>
            <div class="baris">
                <label for="nominal">Nominal (Rp) <span class="wajib">*</span></label>
                <input type="number" id="nominal" name="nominal" value="{{ old('nominal') }}" required min="1000" step="1000" placeholder="mis. 100000">
            </div>
            <div class="baris">
                <label for="tujuan">Untuk</label>
                <select id="tujuan" name="tujuan">
                    <option value="Infaq umum">Infaq umum</option>
                    @foreach ($wakaf as $w)
                        <option value="{{ $w->nama }}" @selected(old('tujuan') === $w->nama)>{{ $w->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="baris">
            <label for="keterangan">Keterangan tambahan</label>
            <textarea id="keterangan" name="keterangan" rows="3" maxlength="1000" placeholder="mis. infaq atas nama keluarga, atau titipan untuk program tertentu">{{ old('keterangan') }}</textarea>
        </div>
        <div class="baris">
            <label for="bukti">Bukti transfer (foto, tidak wajib)</label>
            <input type="file" id="bukti" name="bukti" accept="image/*">
            <small>Format gambar, maksimal 2 MB. Bila gambar besar, unggah lewat tombol WhatsApp di atas.</small>
        </div>
        <button class="tombol-kirim" type="submit">Kirim catatan infaq</button>
    </form>

    {{-- ================= popup cara berinfaq ================= --}}
    <div class="infaq-selubung" onclick="tutupInfaq()"></div>
    <div class="infaq-modal" id="infaqModal" role="dialog" aria-modal="true" aria-labelledby="infaqJudul" aria-hidden="true">
        <div class="infaq-kepala">
            <h3 id="infaqJudul">Salurkan Infaq</h3>
            <button type="button" class="infaq-tutup" onclick="tutupInfaq()" aria-label="Tutup">✕</button>
        </div>
        <div class="infaq-isi">
            @include('publik._bayar')
            <a class="tombol-kirim" href="#konfirmasi" data-tutup-ke="#konfirmasi"
               style="display:block;text-align:center;text-decoration:none">Sudah transfer? Isi konfirmasi</a>
            <p style="font-size:.8rem;color:var(--tinta-muda);margin:.7rem 0 0">
                Catatan konfirmasi membuat infaq Anda tampil di laporan keuangan musholla.
            </p>
        </div>
    </div>

    <button type="button" class="infaq-melayang" onclick="bukaInfaq()" aria-label="Buka cara berinfaq">Infaq</button>
@endsection

@push('skrip')
    <script>
        function bukaInfaq() {
            document.body.classList.add('infaq-terbuka');
            var m = document.getElementById('infaqModal');
            if (m) m.setAttribute('aria-hidden', 'false');
        }
        function tutupInfaq() {
            document.body.classList.remove('infaq-terbuka');
            var m = document.getElementById('infaqModal');
            if (m) m.setAttribute('aria-hidden', 'true');
        }
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutupInfaq(); });

        function salinTeks(teks, tombol) {
            var beres = function () {
                var asli = tombol.getAttribute('data-asli') || tombol.textContent;
                tombol.setAttribute('data-asli', asli);
                tombol.textContent = 'Tersalin ✓';
                tombol.classList.add('sudah');
                setTimeout(function () { tombol.textContent = asli; tombol.classList.remove('sudah'); }, 2000);
            };
            var manual = function () {
                var area = document.createElement('textarea');
                area.value = teks;
                area.style.position = 'fixed';
                area.style.opacity = '0';
                document.body.appendChild(area);
                area.select();
                try { document.execCommand('copy'); beres(); }
                catch (err) { window.prompt('Salin nomor rekening:', teks); }
                document.body.removeChild(area);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(teks).then(beres).catch(manual);
            } else {
                manual();
            }
        }

        document.addEventListener('click', function (e) {
            var salin = e.target.closest('[data-salin]');
            if (salin) { salinTeks(salin.getAttribute('data-salin'), salin); return; }

            var ke = e.target.closest('[data-tutup-ke]');
            if (ke) {
                tutupInfaq();
                var sasaran = document.querySelector(ke.getAttribute('data-tutup-ke'));
                if (sasaran) setTimeout(function () { sasaran.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 120);
            }
        });
    </script>
@endpush
