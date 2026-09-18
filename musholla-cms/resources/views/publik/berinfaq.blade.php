@extends('layouts.publik')

@section('judul', 'Mari Berinfaq — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', 'Salurkan infaq, sedekah, dan wakaf Anda untuk kemakmuran Musholla Al Karim — rekening & QRIS resmi.')

@php
    $waPengurus = preg_replace('/\D/', '', (string) ($pengaturan['kontak_wa'] ?? ''));
    $pesanWa = rawurlencode("Assalamu'alaikum, saya sudah menyalurkan infaq ke Musholla Al Karim. Berikut bukti transfernya.");
    $targetOperasional = (float) ($pengaturan['operasional_bulanan'] ?? 0);
    $biayaMakan = (float) ($pengaturan['makan_harian'] ?? 0);
    $persenOperasional = $targetOperasional > 0 ? min(100, (int) round($operasionalBulanIni / $targetOperasional * 100)) : 0;
@endphp

@section('isi')
    <div class="remah"><a href="/">Beranda</a> &nbsp;›&nbsp; Mari Berinfaq</div>

    @if ($terkirim)
        <div class="pesan-sukses">
            <strong>Terima kasih!</strong> Catatan infaq Anda sudah kami terima dan akan diperiksa pengurus.
            Setelah diverifikasi, catatannya otomatis masuk ke laporan keuangan musholla.
            @if ($waPengurus !== '')
                <div style="margin-top:.7rem">
                    <a class="tombol-kirim" href="https://wa.me/{{ $waPengurus }}?text={{ $pesanWa }}" target="_blank" rel="noopener"
                       style="display:inline-block;text-decoration:none">Kirim bukti lewat WhatsApp</a>
                </div>
            @endif
        </div>
    @endif

    <section class="pahlawan">
        <h1>Mari Berinfaq</h1>
        <p>Setiap infaq Anda menjadi cahaya bagi musholla: makan gratis untuk jamaah, listrik, kebersihan, dan sarana ibadah.</p>
        <div class="aksi">
            <button type="button" class="tombol pemicu-infaq" onclick="bukaInfaq()">Infaq sekarang</button>
            <a class="tombol garis" href="#program">Lihat program</a>
        </div>
        <p class="catatan-tanpa-js" style="font-size:.85rem;margin:.9rem 0 0;opacity:.92">
            Formulir konfirmasi terbuka pada jendela kecil setelah menekan <strong>Infaq sekarang</strong> —
            aktifkan JavaScript di peramban, atau hubungi pengurus
            @if (! empty($pengaturan['kontak_wa']))
                di <a href="https://wa.me/{{ preg_replace('/\D/', '', $pengaturan['kontak_wa']) }}" target="_blank" rel="noopener" style="color:#fff;text-decoration:underline">WhatsApp</a>
            @endif
            untuk menyalurkan infaq.
        </p>
    </section>

    <h2 class="bagian" id="program">Pilih Program</h2>
    <div class="jaring tiga" style="margin-bottom:1.4rem">
        {{-- Program bawaan musholla --}}
        <div class="kartu">
            <div class="tanggal">Program Harian</div>
            <h3>Infaq Makan Gratis</h3>
            <p>Makan siang gratis bagi jamaah yang hadir sholat zuhur di musholla@if ($biayaMakan > 0), rata-rata Rp {{ number_format($biayaMakan, 0, ',', '.') }} per hari (minimal 10 porsi)@endif.</p>
            <p style="margin-top:.7rem">Terkumpul <strong>Rp {{ number_format($makanGratis, 0, ',', '.') }}</strong></p>
            <p style="margin-top:.6rem">
                <a class="tombol-kecil pemicu-program" href="#formInfaq" data-program="Infaq Makan Gratis">Infaq untuk program ini</a>
            </p>
        </div>

        <div class="kartu">
            <div class="tanggal">Program Bulanan</div>
            <h3>Infaq Operasional</h3>
            <p>Mendukung biaya listrik, air, kebersihan, dan pemeliharaan harian demi kenyamanan ibadah di musholla.</p>
            @if ($targetOperasional > 0)
                <div style="height:8px;border-radius:6px;background:var(--hijau-muda);margin:.7rem 0 .4rem;overflow:hidden">
                    <span style="display:block;height:100%;width:{{ $persenOperasional }}%;background:var(--hijau-lembut)"></span>
                </div>
                <p style="margin:0">Bulan ini <strong>Rp {{ number_format($operasionalBulanIni, 0, ',', '.') }}</strong>
                    dari kebutuhan Rp {{ number_format($targetOperasional, 0, ',', '.') }} ({{ $persenOperasional }}%)</p>
            @else
                <p style="margin-top:.7rem">Terkumpul bulan ini <strong>Rp {{ number_format($operasionalBulanIni, 0, ',', '.') }}</strong></p>
            @endif
            <p style="margin-top:.6rem">
                <a class="tombol-kecil pemicu-program" href="#formInfaq" data-program="Infaq Operasional">Infaq untuk program ini</a>
            </p>
        </div>

        {{-- Program wakaf (dikelola pengurus di panel) --}}
        @foreach ($wakaf as $w)
            @php $persenWakaf = $w->target > 0 ? min(100, (int) round(((float) $w->terkumpul / max(1, (float) $w->target)) * 100)) : 0; @endphp
            <div class="kartu">
                <div class="tanggal">Wakaf</div>
                <h3>{{ $w->nama }}</h3>
                <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $w->keterangan), 180) }}</p>
                @if ($w->target > 0)
                    <div style="height:8px;border-radius:6px;background:var(--hijau-muda);margin:.7rem 0 .4rem;overflow:hidden">
                        <span style="display:block;height:100%;width:{{ $persenWakaf }}%;background:var(--hijau-lembut)"></span>
                    </div>
                    <p style="margin:0">Terkumpul <strong>Rp {{ number_format((float) $w->terkumpul, 0, ',', '.') }}</strong>
                        dari Rp {{ number_format((float) $w->target, 0, ',', '.') }} ({{ $persenWakaf }}%)</p>
                @else
                    <p style="margin-top:.7rem">Terkumpul <strong>Rp {{ number_format((float) $w->terkumpul, 0, ',', '.') }}</strong></p>
                @endif
                <p style="margin-top:.6rem">
                    <a class="tombol-kecil pemicu-program" href="#formInfaq" data-program="{{ $w->nama }}">Infaq untuk program ini</a>
                </p>
            </div>
        @endforeach
    </div>

    {{-- ============ popup: rekening + QRIS + formulir nominal ============ --}}
    <div class="infaq-selubung" onclick="tutupInfaq()"></div>
    <div class="infaq-modal" id="infaqModal" role="dialog" aria-modal="true" aria-labelledby="infaqJudul" aria-hidden="true">
        <div class="infaq-kepala">
            <h3 id="infaqJudul">Salurkan Infaq</h3>
            <button type="button" class="infaq-tutup tombol-tutup-popup" onclick="tutupInfaq()" aria-label="Tutup">✕</button>
        </div>
        <div class="infaq-isi">
            <p class="infaq-label">1. Salurkan lewat</p>
            @include('publik._bayar')

            <p class="infaq-label" style="margin-top:1rem">2. Isi konfirmasi</p>

            @if ($errors->any())
                <div class="pesan-galat">
                    <strong>Ada yang perlu diperbaiki:</strong>
                    <ul style="margin:.4rem 0 0 1.1rem">
                        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <form id="formInfaq" class="form-kartu" method="post" action="/mari-berinfaq" enctype="multipart/form-data">
                @csrf

                <div class="baris">
                    <label for="nominal">Nominal (Rp) <span class="wajib">*</span></label>
                    <div class="nominal-cepat">
                        @foreach ([10000, 25000, 50000, 100000, 250000] as $n)
                            <button type="button" data-nominal="{{ $n }}">Rp {{ number_format($n, 0, ',', '.') }}</button>
                        @endforeach
                    </div>
                    <input type="number" id="nominal" name="nominal" value="{{ old('nominal') }}" required min="1000" step="1000" placeholder="mis. 50000">
                </div>

                <div class="baris">
                    <label for="tujuan">Untuk program</label>
                    <select id="tujuan" name="tujuan">
                        <option value="Infaq umum">Infaq umum</option>
                        <option value="Infaq Makan Gratis" @selected(old('tujuan') === 'Infaq Makan Gratis')>Infaq Makan Gratis</option>
                        <option value="Infaq Operasional" @selected(old('tujuan') === 'Infaq Operasional')>Infaq Operasional</option>
                        @foreach ($wakaf as $w)
                            <option value="{{ $w->nama }}" @selected(old('tujuan') === $w->nama)>{{ $w->nama }}</option>
                        @endforeach
                    </select>
                </div>

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
                    <label for="keterangan">Keterangan tambahan</label>
                    <textarea id="keterangan" name="keterangan" rows="2" maxlength="1000" placeholder="mis. infaq atas nama keluarga">{{ old('keterangan') }}</textarea>
                </div>

                <div class="baris">
                    <label for="bukti">Bukti transfer (foto, tidak wajib)</label>
                    <input type="file" id="bukti" name="bukti" accept="image/*">
                    <small>Format gambar, maksimal 2 MB.</small>
                </div>

                <button class="tombol-kirim" type="submit" style="width:100%">Kirim catatan infaq</button>
                <p style="font-size:.78rem;color:var(--tinta-muda);margin:.6rem 0 0">
                    Catatan ini yang membuat infaq Anda tercatat &amp; tampil di laporan keuangan musholla.
                </p>
            </form>
        </div>
    </div>

    <button type="button" class="infaq-melayang" onclick="bukaInfaq()" aria-label="Buka cara berinfaq">Infaq</button>
@endsection

@push('skrip')
    <script>
        function bukaInfaq(program) {
            document.body.classList.add('infaq-terbuka');
            var m = document.getElementById('infaqModal');
            if (m) m.setAttribute('aria-hidden', 'false');
            if (program) {
                var pilih = document.getElementById('tujuan');
                if (pilih) {
                    for (var i = 0; i < pilih.options.length; i++) {
                        if (pilih.options[i].value === program) { pilih.selectedIndex = i; break; }
                    }
                }
            }
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

            var nominal = e.target.closest('[data-nominal]');
            if (nominal) {
                var isi = document.getElementById('nominal');
                if (isi) {
                    isi.value = nominal.getAttribute('data-nominal');
                    document.querySelectorAll('.nominal-cepat button').forEach(function (b) { b.classList.remove('aktif'); });
                    nominal.classList.add('aktif');
                    isi.focus();
                }
                return;
            }

            var program = e.target.closest('[data-program]');
            if (program) {
                e.preventDefault();
                bukaInfaq(program.getAttribute('data-program'));
            }
        });

        // bila pengiriman gagal (ada galat) atau ada isian lama, popup langsung terbuka
        @if ($errors->any() || old('nama_donatur'))
            document.addEventListener('DOMContentLoaded', function () { bukaInfaq(); });
        @endif
    </script>
@endpush
