{{--
    Widget unggah berkas gambar — satu wadah untuk semua kebutuhan.

    Pemakaian:
      @include('partials.unggah-gambar', ['nama' => 'gambar_path', 'label' => 'Gambar sampul', 'nilai' => $lama, 'mode' => 'potong'])
      @include('partials.unggah-gambar', ['nama' => 'bukti_path', 'label' => 'Bukti transfer', 'nilai' => $lama, 'bolehPdf' => true])

    mode 'potong' : bentuk dipaksa persegi (QRIS, logo, gambar sampul berita).
    mode 'bebas'  : bentuk apa adanya, hanya ukurannya dirapikan (foto nota/bukti/struk).
                    — TIDAK ada pemaksaan 1:1 untuk bukti.

    Tanpa JavaScript: input berkas biasa tetap tampil & berfungsi (blok <noscript> di bawah).
    Berkas gambar besar dikecilkan di sisi peramban (sisi terpanjang 1600px, JPEG) supaya
    kiriman dari kamera HP tidak ditolak batas ukuran server.
--}}
@php
    $namaInput = $nama ?? 'gambar';
    $labelInput = $label ?? 'Berkas';
    $nilaiLama = $nilai ?? null;
    $modeInput = ($mode ?? 'bebas') === 'potong' ? 'potong' : 'bebas';
    $bolehPdf = ! empty($bolehPdf);
    $maksMb = $maksMb ?? 5;
    $idInput = 'unggah-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $namaInput);

    $berkasLama = $nilaiLama ? ltrim((string) $nilaiLama, '/') : null;
    $lamaPdf = $berkasLama && str_ends_with(strtolower($berkasLama), '.pdf');
    $tautanLama = $berkasLama ? url('/berkas/' . $berkasLama) : null;
    $ketentuan = ($bolehPdf ? 'JPG, PNG, WEBP, atau PDF' : 'JPG, PNG, atau WEBP') . ' · maksimal ' . $maksMb . ' MB';
    $keteranganKosong = $modeInput === 'potong'
        ? $ketentuan . ' · hasil dipotong persegi'
        : $ketentuan . ' · berkas besar dikecilkan otomatis';
@endphp

<div class="unggah" data-unggah data-mode="{{ $modeInput }}" data-maks="{{ (int) $maksMb }}">
    <span class="unggah-judul">
        {{ $labelInput }}@if (! empty($wajib)) <span class="unggah-wajib">*</span>@endif
    </span>

    <input class="unggah-masuk" type="file" id="{{ $idInput }}" name="{{ $namaInput }}"
           accept="{{ $bolehPdf ? 'image/*,application/pdf' : 'image/*' }}"
           data-unggah-masuk @if (! empty($wajib)) required @endif>

    <noscript>
        <style>
            #{{ $idInput }}.unggah-masuk { position: static; width: 100%; height: auto; clip: auto; overflow: visible; }
        </style>
    </noscript>

    <label class="unggah-tombol" for="{{ $idInput }}">
        <span class="unggah-ikon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 16V4m0 0L8 8m4-4 4 4"/><path d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
            </svg>
        </span>
        <span class="unggah-teks">
            <strong>{{ $berkasLama ? 'Pilih berkas pengganti' : 'Ketuk untuk memilih berkas' }}</strong>
            <small>{{ $keteranganKosong }}</small>
        </span>
    </label>

    {{-- hasil pilihan baru (semua mode) --}}
    <div class="unggah-baru" data-unggah-baru hidden>
        <div class="unggah-cuil">
            <div class="unggah-foto" data-unggah-foto hidden>
                <img data-unggah-pratinjau alt="Pratinjau berkas yang dipilih">
            </div>
            <span class="unggah-chip" data-unggah-chip hidden>PDF</span>
        </div>
        <div class="unggah-meta">
            <strong data-unggah-namaberkas>—</strong>
            <span data-unggah-info></span>
            <div class="unggah-aksi">
                <button type="button" data-unggah-ganti>Ganti</button>
                <button type="button" data-unggah-buang>Buang</button>
            </div>
        </div>
    </div>

    {{-- perkakas potong: hanya mode persegi --}}
    @if ($modeInput === 'potong')
        <div class="unggah-potong" data-unggah-area hidden>
            <p class="unggah-petunjuk">Geser gambar dengan jari/mouse, atur besar-kecil, lalu tekan <strong>Pakai gambar ini</strong>.</p>
            <div class="unggah-bingkai">
                <canvas data-unggah-kanvas width="360" height="360"></canvas>
            </div>
            <div class="unggah-kendali">
                <label class="unggah-zoom" for="{{ $idInput }}-zoom">Besar-kecil</label>
                <input type="range" id="{{ $idInput }}-zoom" min="1" max="3" step="0.01" value="1" data-unggah-zoom>
            </div>
            <div class="unggah-aksi">
                <button type="button" class="utama" data-unggah-pakai>Pakai gambar ini</button>
                <button type="button" data-unggah-batal>Batal</button>
            </div>
        </div>
    @endif

    {{-- berkas yang sudah tersimpan --}}
    @if ($berkasLama)
        <div class="unggah-lama">
            @if ($lamaPdf)
                <a class="unggah-lama-tautan" href="{{ $tautanLama }}" target="_blank" rel="noopener"
                   data-pratinjau="{{ $tautanLama }}" data-pratinjau-nama="{{ basename($berkasLama) }}" title="Lihat dokumen">
                    <span class="unggah-chip">PDF</span>
                </a>
            @else
                <a class="unggah-lama-tautan" href="{{ $tautanLama }}" target="_blank" rel="noopener"
                   data-pratinjau="{{ $tautanLama }}" data-pratinjau-nama="{{ basename($berkasLama) }}" title="Lihat berkas">
                    <img src="{{ $tautanLama }}" alt="Berkas tersimpan" loading="lazy">
                </a>
            @endif
            <span>Tersimpan saat ini — <a href="{{ $tautanLama }}" target="_blank" rel="noopener"
                    data-pratinjau="{{ $tautanLama }}" data-pratinjau-nama="{{ basename($berkasLama) }}">lihat berkas</a></span>
        </div>
    @endif
</div>

@once
    @push('gaya')
        <style>
            .unggah { margin-bottom: 1.05rem; }
            .unggah-judul { display: block; font-size: .82rem; font-weight: 600; color: var(--hijau-tua, #2f6046); margin-bottom: .35rem; }
            .unggah-wajib { color: #b1484f; }

            /* input asli disembunyikan bila JavaScript aktif — tanpa JS blok <noscript> menampilkannya kembali.
               Selektor sengaja spesifik supaya menang atas gaya formulir milik layout (mis. .form-kartu input). */
            .unggah input.unggah-masuk[type=file] {
                position: absolute; width: 1px; height: 1px; min-width: 0; padding: 0; margin: -1px;
                overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; background: transparent;
            }

            .unggah .unggah-tombol {
                display: flex; align-items: center; gap: .7rem; margin: 0; cursor: pointer;
                border: 1px dashed var(--hijau-garis, #d8e7de); border-radius: 12px;
                background: #fbfdfc; padding: .7rem .85rem; font-size: 1rem; font-weight: 500;
                transition: background .15s, border-color .15s;
            }
            .unggah .unggah-tombol:hover { background: var(--hijau-muda, #edf5f0); border-color: var(--hijau-lembut, #6aa383); }
            .unggah .unggah-masuk:focus-visible ~ .unggah-tombol { outline: 2px solid rgba(63, 125, 92, .35); outline-offset: 2px; }
            .unggah-ikon {
                flex: 0 0 auto; width: 34px; height: 34px; border-radius: 10px; display: inline-flex;
                align-items: center; justify-content: center; background: var(--hijau-muda, #edf5f0); color: var(--hijau-tua, #2f6046);
            }
            .unggah-ikon svg { width: 19px; height: 19px; }
            .unggah-teks { display: flex; flex-direction: column; min-width: 0; }
            .unggah-teks strong { font-size: .88rem; font-weight: 600; color: var(--hijau-tua, #2f6046); }
            .unggah-teks small { font-size: .75rem; color: var(--tinta-muda, #64766c); }
            .unggah.ada-berkas .unggah-tombol { display: none; }

            .unggah-baru { display: flex; align-items: flex-start; gap: .8rem; margin-top: .6rem; }
            /* display pada kelas di atas menimpa gaya bawaan [hidden] → kembalikan sembunyinya */
            .unggah-baru[hidden], .unggah-foto[hidden], .unggah-chip[hidden], .unggah-potong[hidden] { display: none; }
            .unggah-cuil { flex: 0 0 auto; }
            .unggah-foto {
                width: 78px; height: 78px; border-radius: 10px; border: 1px solid var(--garis, #e2ebe5);
                background: #fff; overflow: hidden; display: flex; align-items: center; justify-content: center;
            }
            .unggah-foto img { max-width: 100%; max-height: 100%; object-fit: contain; display: block; }
            .unggah-chip {
                display: inline-flex; align-items: center; justify-content: center; width: 78px; height: 78px;
                border-radius: 10px; border: 1px solid var(--garis, #e2ebe5); background: #f4f7f5;
                color: var(--tinta-muda, #64766c); font-size: .72rem; font-weight: 700; letter-spacing: .5px;
            }
            .unggah-meta { display: flex; flex-direction: column; gap: .25rem; min-width: 0; font-size: .8rem; color: var(--tinta-muda, #64766c); }
            .unggah-meta strong { font-size: .85rem; color: var(--tinta, #22302a); overflow-wrap: anywhere; }
            .unggah-aksi { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .35rem; }
            .unggah-aksi button {
                font: inherit; font-size: .8rem; font-weight: 600; padding: .34rem .7rem; border-radius: 9px;
                border: 1px solid var(--garis, #e2ebe5); background: #fff; color: var(--hijau-tua, #2f6046); cursor: pointer;
            }
            .unggah-aksi button:hover { background: var(--hijau-muda, #edf5f0); }
            .unggah-aksi button.utama { background: var(--hijau, #3f7d5c); border-color: var(--hijau, #3f7d5c); color: #fff; }

            .unggah-potong { margin-top: .7rem; border: 1px solid var(--garis, #e2ebe5); border-radius: 12px; padding: .85rem; background: #fbfdfc; }
            .unggah-potong[hidden] { display: none; }
            .unggah-petunjuk { margin: 0 0 .6rem; font-size: .78rem; color: var(--tinta-muda, #64766c); }
            .unggah-bingkai { width: 100%; max-width: 320px; }
            .unggah-bingkai canvas {
                width: 100%; height: auto; aspect-ratio: 1 / 1; display: block; border-radius: 12px;
                border: 1px solid var(--garis, #e2ebe5); background: #fff; touch-action: none; cursor: grab;
            }
            .unggah-kendali { margin: .7rem 0 .1rem; display: flex; flex-direction: column; gap: .25rem; }
            .unggah-zoom { font-size: .75rem; color: var(--tinta-muda, #64766c); }
            .unggah-kendali input[type=range] { width: 100%; max-width: 320px; accent-color: var(--hijau, #3f7d5c); }

            .unggah-lama { display: flex; align-items: center; gap: .6rem; margin-top: .55rem; font-size: .78rem; color: var(--tinta-muda, #64766c); }
            .unggah-lama img {
                width: 52px; height: 52px; object-fit: contain; background: #fff;
                border: 1px solid var(--garis, #e2ebe5); border-radius: 9px;
            }
            .unggah-lama .unggah-chip { width: 52px; height: 52px; }
            .unggah-lama a { color: var(--hijau-tua, #2f6046); text-decoration: underline; }
            .unggah-lama a.unggah-lama-tautan { text-decoration: none; line-height: 0; }
            .unggah-taruh { background: var(--hijau-muda, #edf5f0); border-radius: 12px; }
        </style>
    @endpush

    @push('skrip')
        <script>
        (function () {
            var MAKS_SISI = 1600;              // sisi terpanjang setelah dirapikan
            var RAPIKAN_BILA = 1200 * 1024;    // berkas di atas ini selalu dikecilkan
            var MUTU = 0.85;
            var SISI_POTONG = 800;             // hasil potongan persegi

            var bisaTukar = (function () { try { new DataTransfer(); return true; } catch (e) { return false; } })();

            function ukuran(bit) {
                if (bit < 1024) return bit + ' B';
                if (bit < 1048576) return Math.round(bit / 1024) + ' KB';
                return (bit / 1048576).toFixed(1).replace('.', ',') + ' MB';
            }

            function namaJpg(nama) {
                return (nama || 'berkas').replace(/\.[^.]+$/, '') + '-rapi.jpg';
            }

            function gambarDari(berkas, url, saatSiap) {
                if (window.createImageBitmap) {
                    createImageBitmap(berkas, { imageOrientation: 'from-image' })
                        .then(saatSiap)
                        .catch(function () { var i = new Image(); i.onload = function () { saatSiap(i); }; i.src = url; });
                } else {
                    var i2 = new Image();
                    i2.onload = function () { saatSiap(i2); };
                    i2.src = url;
                }
            }

            document.querySelectorAll('[data-unggah]').forEach(function (wadah) {
                var masuk = wadah.querySelector('[data-unggah-masuk]');
                if (!masuk) return;

                var mode = wadah.getAttribute('data-mode');
                var blokBaru = wadah.querySelector('[data-unggah-baru]');
                var foto = wadah.querySelector('[data-unggah-foto]');
                var pratinjau = wadah.querySelector('[data-unggah-pratinjau]');
                var chip = wadah.querySelector('[data-unggah-chip]');
                var namaBerkas = wadah.querySelector('[data-unggah-namaberkas]');
                var info = wadah.querySelector('[data-unggah-info]');
                var area = wadah.querySelector('[data-unggah-area]');
                var kanvas = wadah.querySelector('[data-unggah-kanvas]');
                var zoom = wadah.querySelector('[data-unggah-zoom]');
                var ctx = kanvas ? kanvas.getContext('2d') : null;

                var gambar = null, dasar = 1, skala = 1, geserX = 0, geserY = 0, seret = false, mulaiX = 0, mulaiY = 0;

                function tampilkan(berkas, urlPratinjau, keterangan, pdf) {
                    if (foto) foto.hidden = !!pdf;
                    if (chip) chip.hidden = !pdf;
                    if (pratinjau && urlPratinjau) pratinjau.src = urlPratinjau;
                    if (namaBerkas) namaBerkas.textContent = berkas.name;
                    if (info) info.textContent = keterangan;
                    if (blokBaru) blokBaru.hidden = false;
                    if (area) area.hidden = true;
                    wadah.classList.add('ada-berkas');
                }

                function bersihkan() {
                    masuk.value = '';
                    gambar = null;
                    if (blokBaru) blokBaru.hidden = true;
                    if (area) area.hidden = true;
                    if (pratinjau) pratinjau.removeAttribute('src');
                    wadah.classList.remove('ada-berkas');
                }

                function pakaiBerkasAsli(berkas, url, keterangan) {
                    tampilkan(berkas, url, keterangan + ' — siap diunggah.');
                }

                function gambarUlang() {
                    if (!kanvas || !gambar) return;
                    var w = kanvas.width, h = kanvas.height;
                    ctx.fillStyle = '#fff';
                    ctx.fillRect(0, 0, w, h);
                    var s = dasar * skala;
                    var gw = gambar.width * s, gh = gambar.height * s;
                    var maksX = Math.max(0, (gw - w) / 2), maksY = Math.max(0, (gh - h) / 2);
                    geserX = Math.min(maksX, Math.max(-maksX, geserX));
                    geserY = Math.min(maksY, Math.max(-maksY, geserY));
                    ctx.drawImage(gambar, (w - gw) / 2 + geserX, (h - gh) / 2 + geserY, gw, gh);
                }

                /* ---- mode bebas: rapikan ukuran, tanpa memotong bentuk ---- */
                function rapikan(bitmap, berkas, url) {
                    var sisi = Math.max(bitmap.width, bitmap.height);
                    if (sisi <= MAKS_SISI && berkas.size <= RAPIKAN_BILA) {
                        pakaiBerkasAsli(berkas, url, 'Ukuran ' + bitmap.width + '×' + bitmap.height + ' · ' + ukuran(berkas.size));
                        return;
                    }

                    var faktor = Math.min(1, MAKS_SISI / sisi);
                    var w = Math.max(1, Math.round(bitmap.width * faktor));
                    var h = Math.max(1, Math.round(bitmap.height * faktor));
                    var kanvasRapi = document.createElement('canvas');
                    kanvasRapi.width = w;
                    kanvasRapi.height = h;
                    var k = kanvasRapi.getContext('2d');
                    k.fillStyle = '#fff';
                    k.fillRect(0, 0, w, h);
                    k.drawImage(bitmap, 0, 0, w, h);

                    kanvasRapi.toBlob(function (blob) {
                        if (!blob) { pakaiBerkasAsli(berkas, url, 'Ukuran ' + bitmap.width + '×' + bitmap.height + ' · ' + ukuran(berkas.size)); return; }
                        var hasil = new File([blob], namaJpg(berkas.name), { type: 'image/jpeg' });
                        var terpakai = hasil;
                        if (bisaTukar) {
                            try {
                                var dt = new DataTransfer();
                                dt.items.add(hasil);
                                masuk.files = dt.files;
                            } catch (e) {
                                terpakai = berkas;
                            }
                        } else {
                            terpakai = berkas;
                        }
                        var keterangan = terpakai === hasil
                            ? 'Dirapikan ' + bitmap.width + '×' + bitmap.height + ' → ' + w + '×' + h + ' · ' + ukuran(blob.size)
                            : 'Ukuran ' + bitmap.width + '×' + bitmap.height + ' · ' + ukuran(berkas.size);
                        tampilkan(terpakai, terpakai === hasil ? URL.createObjectURL(blob) : url, keterangan + ' — siap diunggah.');
                    }, 'image/jpeg', MUTU);
                }

                /* ---- mode potong: paksa persegi ---- */
                function mulaiPotong(bitmap, berkas, url) {
                    if (!kanvas || !bisaTukar) { pakaiBerkasAsli(berkas, url, 'Ukuran ' + bitmap.width + '×' + bitmap.height + ' · ' + ukuran(berkas.size)); return; }
                    gambar = bitmap;
                    dasar = Math.max(kanvas.width / bitmap.width, kanvas.height / bitmap.height);
                    skala = 1; geserX = 0; geserY = 0;
                    if (zoom) zoom.value = 1;
                    if (blokBaru) blokBaru.hidden = true;
                    if (area) area.hidden = false;
                    gambarUlang();
                }

                function tanganiBerkas(berkas) {
                    if (!berkas) return;
                    var url = URL.createObjectURL(berkas);
                    var pdf = berkas.type === 'application/pdf' || /\.pdf$/i.test(berkas.name);
                    if (pdf) { tampilkan(berkas, null, 'Dokumen PDF · ' + ukuran(berkas.size) + ' — siap diunggah.', true); return; }
                    if (!berkas.type.match(/^image\//)) { tampilkan(berkas, null, ukuran(berkas.size) + ' — siap diunggah.', true); return; }
                    gambarDari(berkas, url, function (bitmap) {
                        if (mode === 'potong') mulaiPotong(bitmap, berkas, url);
                        else rapikan(bitmap, berkas, url);
                    });
                }

                masuk.addEventListener('change', function () {
                    if (masuk.files && masuk.files[0]) tanganiBerkas(masuk.files[0]);
                });

                if (zoom) zoom.addEventListener('input', function () { skala = parseFloat(zoom.value) || 1; gambarUlang(); });

                if (kanvas) {
                    kanvas.addEventListener('pointerdown', function (e) {
                        seret = true; mulaiX = e.clientX - geserX; mulaiY = e.clientY - geserY;
                        try { kanvas.setPointerCapture(e.pointerId); } catch (err) {}
                        kanvas.style.cursor = 'grabbing';
                    });
                    kanvas.addEventListener('pointermove', function (e) {
                        if (!seret) return;
                        geserX = e.clientX - mulaiX; geserY = e.clientY - mulaiY;
                        gambarUlang();
                    });
                    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (ev) {
                        kanvas.addEventListener(ev, function () { seret = false; kanvas.style.cursor = 'grab'; });
                    });
                }

                var tombolPakai = wadah.querySelector('[data-unggah-pakai]');
                if (tombolPakai) tombolPakai.addEventListener('click', function () {
                    if (!gambar || !kanvas) return;
                    var keluar = document.createElement('canvas');
                    keluar.width = SISI_POTONG; keluar.height = SISI_POTONG;
                    var k = keluar.getContext('2d');
                    k.fillStyle = '#fff';
                    k.fillRect(0, 0, SISI_POTONG, SISI_POTONG);
                    var rasio = SISI_POTONG / kanvas.width;
                    var s = dasar * skala * rasio;
                    var gw = gambar.width * s, gh = gambar.height * s;
                    k.drawImage(gambar, (SISI_POTONG - gw) / 2 + geserX * rasio, (SISI_POTONG - gh) / 2 + geserY * rasio, gw, gh);
                    keluar.toBlob(function (blob) {
                        if (!blob) return;
                        var hasil = new File([blob], namaJpg(masuk.files && masuk.files[0] ? masuk.files[0].name : 'gambar'), { type: 'image/jpeg' });
                        try {
                            var dt = new DataTransfer();
                            dt.items.add(hasil);
                            masuk.files = dt.files;
                        } catch (err) { /* peramban tua: berkas asli yang terkirim */ }
                        tampilkan(hasil, URL.createObjectURL(blob), 'Persegi ' + SISI_POTONG + '×' + SISI_POTONG + ' · ' + ukuran(blob.size) + ' — siap diunggah.');
                    }, 'image/jpeg', 0.9);
                });

                var tombolBatal = wadah.querySelector('[data-unggah-batal]');
                if (tombolBatal) tombolBatal.addEventListener('click', bersihkan);

                var tombolBuang = wadah.querySelector('[data-unggah-buang]');
                if (tombolBuang) tombolBuang.addEventListener('click', bersihkan);

                var tombolGanti = wadah.querySelector('[data-unggah-ganti]');
                if (tombolGanti) tombolGanti.addEventListener('click', function () { masuk.click(); });

                /* seret-lepas (komputer) */
                ['dragenter', 'dragover'].forEach(function (ev) {
                    wadah.addEventListener(ev, function (e) { e.preventDefault(); wadah.classList.add('unggah-taruh'); });
                });
                ['dragleave', 'dragend'].forEach(function (ev) {
                    wadah.addEventListener(ev, function () { wadah.classList.remove('unggah-taruh'); });
                });
                wadah.addEventListener('drop', function (e) {
                    e.preventDefault();
                    wadah.classList.remove('unggah-taruh');
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                        var berkas = e.dataTransfer.files[0];
                        if (bisaTukar) {
                            try {
                                var dt = new DataTransfer();
                                dt.items.add(berkas);
                                masuk.files = dt.files;
                            } catch (err) {}
                        }
                        tanganiBerkas(berkas);
                    }
                });

                /* kunci tombol kirim + beri tahu bahwa berkas sedang dikirim */
                var form = wadah.closest('form');
                if (form && !form.dataset.unggahSiap) {
                    form.dataset.unggahSiap = '1';
                    form.addEventListener('submit', function () {
                        var adaBerkas = false;
                        form.querySelectorAll('[data-unggah-masuk]').forEach(function (i) { if (i.files && i.files.length) adaBerkas = true; });
                        if (!adaBerkas) return;
                        var tombol = form.querySelector('button[type=submit]');
                        if (!tombol) return;
                        tombol.setAttribute('data-teks-asli', tombol.textContent);
                        setTimeout(function () {
                            tombol.disabled = true;
                            tombol.textContent = 'Mengirim berkas…';
                        }, 0);
                    });
                }
            });
        })();
        </script>
    @endpush
@endonce
