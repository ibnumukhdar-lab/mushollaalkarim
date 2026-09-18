{{--
    Widget unggah gambar + potong (crop) sebelum dikirim.
    Pemakaian:  @include('partials.potong-gambar', ['nama' => 'qris', 'label' => 'Gambar QRIS', 'nilai' => $nilaiLama])

    - Tanpa JavaScript tetap bisa unggah seperti biasa (input berkas biasa).
    - Potongan berbentuk PERSEGI (1:1) — cocok untuk QRIS.
--}}
@php
    $namaInput = $nama ?? 'gambar';
    $labelInput = $label ?? 'Gambar';
    $nilaiLama = $nilai ?? null;
    $idInput = 'potong-' . $namaInput;
@endphp

<div class="potong" data-potong>
    <label for="{{ $idInput }}">{{ $labelInput }}</label>
    <input type="file" id="{{ $idInput }}" name="{{ $namaInput }}" accept="image/*"
           data-potong-masuk @if (! empty($wajib)) required @endif>

    <div class="potong-area" data-potong-area hidden>
        <p class="potong-petunjuk">Geser gambar dengan jari/mouse, atur besar-kecil, lalu tekan <strong>Potong &amp; pakai</strong>.</p>
        <div class="potong-bingkai">
            <canvas data-potong-kanvas width="340" height="340"></canvas>
        </div>
        <div class="potong-kendali">
            <label class="potong-zoom-label" for="{{ $idInput }}-zoom">Besar-kecil</label>
            <input type="range" id="{{ $idInput }}-zoom" min="1" max="3" step="0.01" value="1" data-potong-zoom>
            <div class="potong-tombol">
                <button type="button" class="tbl-kecil utama" data-potong-pakai>Potong &amp; pakai</button>
                <button type="button" class="tbl-kecil" data-potong-batal>Batal</button>
            </div>
        </div>
    </div>

    <div class="potong-hasil" data-potong-hasil hidden>
        <img data-potong-pratinjau alt="Pratinjau gambar yang dipotong">
        <div class="potong-ket">
            <span data-potong-info>Gambar siap diunggah.</span>
            <button type="button" class="tbl-kecil" data-potong-ulang>Pilih gambar lain</button>
        </div>
    </div>

    @if ($nilaiLama)
        <div class="potong-lama">
            <img src="{{ url('/berkas/' . ltrim($nilaiLama, '/')) }}" alt="Gambar saat ini">
            <span>Gambar tersimpan saat ini — biarkan kosong bila tidak ingin mengganti.</span>
        </div>
    @endif
</div>

@once
    @push('gaya')
        <style>
            .potong { margin-bottom: .95rem; }
            .potong > label { display: block; font-size: .82rem; font-weight: 600; color: var(--hijau-tua); margin-bottom: .3rem; }
            .potong input[type=file] {
                width: 100%; font: inherit; font-size: .9rem; padding: .5rem .6rem;
                border: 1px solid var(--garis, #e2ebe5); border-radius: 10px; background: #fff;
            }
            .potong-area { margin-top: .7rem; border: 1px solid var(--garis, #e2ebe5); border-radius: 12px; padding: .8rem; background: #fbfdfc; }
            .potong-petunjuk { margin: 0 0 .6rem; font-size: .82rem; color: var(--tinta-muda, #64766c); }
            .potong-bingkai { width: 100%; max-width: 360px; }
            .potong-bingkai canvas {
                width: 100%; height: auto; aspect-ratio: 1 / 1; border-radius: 12px;
                border: 1px solid var(--garis, #e2ebe5); background: #fff; touch-action: none; cursor: grab;
            }
            .potong-kendali { margin-top: .7rem; display: flex; flex-direction: column; gap: .5rem; }
            .potong-zoom-label { font-size: .78rem; color: var(--tinta-muda, #64766c); }
            .potong-kendali input[type=range] { width: 100%; max-width: 360px; accent-color: var(--hijau, #3f7d5c); }
            .potong-tombol { display: flex; gap: .5rem; flex-wrap: wrap; }
            .tbl-kecil {
                font: inherit; font-size: .85rem; font-weight: 600; padding: .45rem .85rem; border-radius: 10px;
                border: 1px solid var(--garis, #e2ebe5); background: #fff; color: var(--hijau-tua, #2f6046); cursor: pointer;
            }
            .tbl-kecil.utama { background: var(--hijau, #3f7d5c); color: #fff; border-color: var(--hijau, #3f7d5c); }
            .tbl-kecil:hover { opacity: .93; }
            .potong-hasil { margin-top: .7rem; display: flex; gap: .8rem; align-items: center; flex-wrap: wrap; }
            .potong-hasil img { width: 96px; height: 96px; object-fit: cover; border-radius: 12px; border: 1px solid var(--garis, #e2ebe5); }
            .potong-ket { display: flex; flex-direction: column; gap: .4rem; font-size: .82rem; color: var(--tinta-muda, #64766c); }
            .potong-lama { display: flex; gap: .7rem; align-items: center; margin-top: .6rem; font-size: .8rem; color: var(--tinta-muda, #64766c); }
            .potong-lama img { width: 84px; height: 84px; object-fit: contain; background: #fff; border: 1px solid var(--garis, #e2ebe5); border-radius: 10px; }
        </style>
    @endpush

    @push('skrip')
        <script>
        (function () {
            document.querySelectorAll('[data-potong]').forEach(function (wadah) {
                var masuk = wadah.querySelector('[data-potong-masuk]');
                var area = wadah.querySelector('[data-potong-area]');
                var hasil = wadah.querySelector('[data-potong-hasil]');
                var kanvas = wadah.querySelector('[data-potong-kanvas]');
                var zoom = wadah.querySelector('[data-potong-zoom]');
                var pratinjau = wadah.querySelector('[data-potong-pratinjau]');
                if (!masuk || !kanvas) return;

                var ctx = kanvas.getContext('2d');
                var gambar = null, skala = 1, dasar = 1, geserX = 0, geserY = 0, seret = false, mulaiX = 0, mulaiY = 0;
                var SISI = 800; // hasil potongan (persegi)

                function gambarUlang() {
                    var w = kanvas.width, h = kanvas.height;
                    ctx.fillStyle = '#fff';
                    ctx.fillRect(0, 0, w, h);
                    if (!gambar) return;
                    var s = dasar * skala;
                    var gw = gambar.width * s, gh = gambar.height * s;
                    var maksX = Math.max(0, (gw - w) / 2), maksY = Math.max(0, (gh - h) / 2);
                    geserX = Math.min(maksX, Math.max(-maksX, geserX));
                    geserY = Math.min(maksY, Math.max(-maksY, geserY));
                    ctx.drawImage(gambar, (w - gw) / 2 + geserX, (h - gh) / 2 + geserY, gw, gh);
                }

                function muatBerkas(berkas) {
                    if (!berkas || !berkas.type.match(/^image\//)) { area.hidden = true; return; }
                    var url = URL.createObjectURL(berkas);
                    var proses = function (img) {
                        gambar = img;
                        dasar = Math.max(kanvas.width / img.width, kanvas.height / img.height);
                        skala = 1; geserX = 0; geserY = 0;
                        if (zoom) zoom.value = 1;
                        area.hidden = false;
                        hasil.hidden = true;
                        gambarUlang();
                    };
                    if (window.createImageBitmap) {
                        createImageBitmap(berkas, { imageOrientation: 'from-image' })
                            .then(function (bmp) { proses(bmp); })
                            .catch(function () { var i = new Image(); i.onload = function () { proses(i); }; i.src = url; });
                    } else {
                        var i2 = new Image();
                        i2.onload = function () { proses(i2); };
                        i2.src = url;
                    }
                }

                masuk.addEventListener('change', function () {
                    if (masuk.files && masuk.files[0]) muatBerkas(masuk.files[0]);
                });

                if (zoom) zoom.addEventListener('input', function () { skala = parseFloat(zoom.value) || 1; gambarUlang(); });

                kanvas.addEventListener('pointerdown', function (e) {
                    seret = true; mulaiX = e.clientX - geserX; mulaiY = e.clientY - geserY;
                    kanvas.setPointerCapture(e.pointerId);
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

                var tombolPakai = wadah.querySelector('[data-potong-pakai]');
                if (tombolPakai) tombolPakai.addEventListener('click', function () {
                    if (!gambar) return;
                    var keluar = document.createElement('canvas');
                    keluar.width = SISI; keluar.height = SISI;
                    var k = keluar.getContext('2d');
                    k.fillStyle = '#fff';
                    k.fillRect(0, 0, SISI, SISI);
                    var s = dasar * skala * (SISI / kanvas.width);
                    var gw = gambar.width * s, gh = gambar.height * s;
                    var rasio = SISI / kanvas.width;
                    k.drawImage(gambar, (SISI - gw) / 2 + geserX * rasio, (SISI - gh) / 2 + geserY * rasio, gw, gh);
                    keluar.toBlob(function (blob) {
                        if (!blob) return;
                        var namaAsli = (masuk.files && masuk.files[0] ? masuk.files[0].name : 'gambar') .replace(/\.[^.]+$/, '') + '-potong.jpg';
                        var berkas = new File([blob], namaAsli, { type: 'image/jpeg' });
                        try {
                            var dt = new DataTransfer();
                            dt.items.add(berkas);
                            masuk.files = dt.files;
                        } catch (err) {
                            // peramban tua: biarkan berkas asli terkirim
                        }
                        if (pratinjau) pratinjau.src = URL.createObjectURL(blob);
                        hasil.hidden = false;
                        area.hidden = true;
                        var info = wadah.querySelector('[data-potong-info]');
                        if (info) info.textContent = 'Gambar sudah dipotong (' + Math.round(blob.size / 1024) + ' KB) dan siap diunggah.';
                    }, 'image/jpeg', 0.9);
                });

                var tombolBatal = wadah.querySelector('[data-potong-batal]');
                if (tombolBatal) tombolBatal.addEventListener('click', function () {
                    masuk.value = '';
                    gambar = null;
                    area.hidden = true;
                    hasil.hidden = true;
                });

                var tombolUlang = wadah.querySelector('[data-potong-ulang]');
                if (tombolUlang) tombolUlang.addEventListener('click', function () {
                    masuk.value = '';
                    hasil.hidden = true;
                    masuk.click();
                });
            });
        })();
        </script>
    @endpush
@endonce
