@extends('layouts.publik')

@section('judul', $tulisan->judul.' — '.($pengaturan['nama_situs'] ?? 'Musholla Al Karim'))
@section('deskripsi', \App\Support\Tulis::ringkas($tulisan->ringkasan ?: $tulisan->isi, 155))

@section('og_tipe', 'article')
@section('og_gambar', \App\Services\KartuSosial::untuk($tulisan) ?: \App\Services\KartuSosial::bawaan())
@section('og_lebar', '1200')
@section('og_tinggi', '630')
@if ($tulisan->terbit_at)
    @section('terbit_pada', $tulisan->terbit_at->toIso8601String())
@endif

@php
    use Illuminate\Support\Str;

    $gambarSampul = $tulisan->gambar_sampul;
    $kategoriTulisan = $tulisan->kategori_daftar;

    // Isi tulisan + pemberian jangkar pada setiap sub-judul (untuk daftar isi)
    $isiHtml = \App\Support\Tulis::keHtml($tulisan->isi);

    $daftarIsi = [];
    $isiHtml = preg_replace_callback(
        '~<(h2|h3)(\s[^>]*)?>(.*?)</\1>~is',
        function ($m) use (&$daftarIsi) {
            $teks = trim(strip_tags($m[3]));
            if ($teks === '') { return $m[0]; }
            $jangkar = 'bagian-' . (count($daftarIsi) + 1);
            $daftarIsi[] = ['id' => $jangkar, 'teks' => $teks, 'besar' => strtolower($m[1]) === 'h2'];
            return '<' . $m[1] . ' id="' . $jangkar . '"' . ($m[2] ?: '') . '>' . $m[3] . '</' . $m[1] . '>';
        },
        $isiHtml,
    ) ?? $isiHtml;

    // Perkiraan waktu baca (200 kata per menit)
    $jmlKata = str_word_count(strip_tags($isiHtml));
    $menitBaca = max(1, (int) ceil($jmlKata / 200));

    $namaPenulis = $tulisan->penulis
        ? ($tulisan->penulis->nama_lengkap ?: $tulisan->penulis->name)
        : ($pengaturan['nama_situs'] ?? 'Pengurus');
    $inisial = Str::upper(Str::substr(trim($namaPenulis), 0, 1));

    $tautanTulisan = url('/berita/' . $tulisan->slug);
    $judulBagikan = $tulisan->judul;
@endphp

@push('gaya')
<style>
    /* ---------- halaman baca tulisan ---------- */
    .baca-kepala { max-width: 720px; margin: 0 auto 1.2rem; }
    .baca-kepala h1 { font-size: 1.62rem; line-height: 1.28; margin: .5rem 0 .8rem; color: var(--hijau-tua); letter-spacing: -.2px; }
    .baca-sampul { display: block; width: 100%; max-width: 520px; aspect-ratio: 1 / 1; object-fit: cover;
        border-radius: 16px; margin: 0 auto 1.3rem; box-shadow: 0 6px 22px rgba(20, 60, 40, .10); }
    .baca-baris { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
        font-size: .82rem; color: var(--tinta-muda); margin-bottom: .9rem; }
    .baca-lingkar { width: 34px; height: 34px; border-radius: 50%; background: var(--hijau-muda); color: var(--hijau-tua);
        display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .95rem; }
    .baca-baris strong { color: var(--hijau-tua); font-weight: 600; }
    .baca-pisah { opacity: .45; }
    .baca-isi { max-width: 720px; margin: 0 auto; font-size: 1.02rem; line-height: 1.85; color: #22352b; }
    .baca-isi > p:first-of-type { font-size: 1.06rem; }
    .baca-isi h2 { font-size: 1.3rem; margin: 2.1rem 0 .7rem; color: var(--hijau-tua); line-height: 1.35; }
    .baca-isi h3 { font-size: 1.1rem; margin: 1.6rem 0 .5rem; color: var(--hijau-tua); }
    .baca-isi p { margin: 1rem 0; }
    .baca-isi a { color: #0f766e; text-decoration: underline; text-underline-offset: 2px; }
    .baca-isi a:hover { color: var(--hijau-tua); }
    .baca-isi ul, .baca-isi ol { padding-left: 1.35rem; margin: 1rem 0; }
    .baca-isi li { margin: .4rem 0; }
    .baca-isi blockquote { margin: 1.4rem 0; padding: .85rem 1.1rem; border-left: 3px solid var(--hijau-lembut);
        background: var(--hijau-muda); border-radius: 0 12px 12px 0; font-style: italic; color: #2b4034; }
    .baca-isi blockquote p { margin: .3rem 0; }
    .baca-isi img { max-width: 100%; height: auto; border-radius: 12px; display: block; margin: 1.3rem auto; }
    .baca-isi figure { margin: 1.4rem 0; }
    .baca-isi figcaption { font-size: .82rem; color: var(--tinta-muda); text-align: center; margin-top: .45rem; }
    .baca-isi table { width: 100%; border-collapse: collapse; font-size: .9rem; display: block; overflow-x: auto; margin: 1.3rem 0; }
    .baca-isi th, .baca-isi td { border: 1px solid var(--garis); padding: .55rem .7rem; text-align: left; }
    .baca-isi thead th { background: var(--hijau-muda); color: var(--hijau-tua); }
    .baca-isi hr { border: 0; border-top: 1px solid var(--garis); margin: 1.8rem 0; }
    .baca-isi code, .baca-isi pre { background: var(--hijau-muda); border-radius: 8px; font-family: ui-monospace, monospace; font-size: .88rem; }
    .baca-isi code { padding: .12rem .4rem; }
    .baca-isi pre { padding: .9rem 1rem; overflow-x: auto; }

    .baca-ringkas { max-width: 720px; margin: 0 auto 1.4rem; font-size: 1.02rem; font-style: italic;
        color: #2b4034; border-left: 3px solid var(--hijau-lembut); padding-left: .95rem; }

    .baca-alat { max-width: 720px; margin: 1.6rem auto 0; padding-top: 1rem; border-top: 1px solid var(--garis);
        display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .baca-alat .label { font-size: .8rem; color: var(--tinta-muda); margin-right: .15rem; }
    .baca-tbl { width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
        background: var(--hijau-muda); color: var(--hijau-tua); border: 0; cursor: pointer; transition: transform .15s ease; }
    .baca-tbl:hover { transform: scale(1.08); }
    .baca-tbl svg { width: 16px; height: 16px; }
    .baca-tbl.wa { background: #25d366; color: #fff; }

    .baca-daftar { max-width: 720px; margin: 0 auto 1.5rem; background: var(--hijau-muda); border-radius: 14px;
        padding: .9rem 1.1rem; font-size: .92rem; }
    .baca-daftar summary { cursor: pointer; font-weight: 600; color: var(--hijau-tua); }
    .baca-daftar ol { margin: .7rem 0 0; padding-left: 1.3rem; }
    .baca-daftar li { margin: .3rem 0; }
    .baca-daftar a { color: #0f766e; text-decoration: none; }
    .baca-daftar a:hover { text-decoration: underline; }
    .baca-daftar .kecil { padding-left: 1.3rem; }

    .baca-cta { max-width: 720px; margin: 1.6rem auto 0; background: var(--hijau-tua); color: #fff; border-radius: 16px;
        padding: 1.1rem 1.2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .baca-cta p { margin: 0; font-size: .92rem; opacity: .95; }
    .baca-cta a { background: #fff; color: var(--hijau-tua); font-weight: 600; font-size: .88rem; padding: .5rem .9rem;
        border-radius: 999px; text-decoration: none; white-space: nowrap; }

    .baca-maju { position: fixed; top: 0; left: 0; height: 3px; background: var(--hijau-lembut); width: 0; z-index: 50; transition: width .1s linear; }
    @media (min-width: 700px) {
        .baca-kepala h1 { font-size: 1.95rem; }
        .baca-isi { font-size: 1.06rem; }
    }
</style>
@endpush

@section('isi')
    <div class="baca-maju" id="bacaMaju" aria-hidden="true"></div>

    <div class="remah">
        <a href="/">Beranda</a> &nbsp;›&nbsp; <a href="/berita">Berita</a> &nbsp;›&nbsp; {{ $tulisan->judul }}
    </div>

    <article>
        <header class="baca-kepala">
            @if (! empty($kategoriTulisan))
                <div class="chip-baris">
                    @foreach ($kategoriTulisan as $namaKategori)
                        <span class="chip-kategori aktif">{{ $namaKategori }}</span>
                    @endforeach
                </div>
            @endif

            <h1>{{ $tulisan->judul }}</h1>

            <div class="baca-baris">
                <span class="baca-lingkar">{{ $inisial }}</span>
                <span><strong>{{ $namaPenulis }}</strong></span>
                <span class="baca-pisah">•</span>
                <span>{{ $tulisan->terbit_at?->translatedFormat('d F Y') }}</span>
                <span class="baca-pisah">•</span>
                <span>± {{ $menitBaca }} menit baca</span>
            </div>
        </header>

        @if ($gambarSampul)
            <img class="baca-sampul" src="{{ $gambarSampul }}" alt="{{ $tulisan->judul }}">
        @endif

        @if (trim((string) $tulisan->ringkasan) !== '')
            <p class="baca-ringkas">{{ \App\Support\Tulis::ringkas($tulisan->ringkasan, 400) }}</p>
        @endif

        @if (count($daftarIsi) >= 3)
            <details class="baca-daftar">
                <summary>Isi tulisan ({{ count($daftarIsi) }} bagian)</summary>
                <ol>
                    @foreach ($daftarIsi as $d)
                        <li @class(['kecil' => ! $d['besar']])><a href="#{{ $d['id'] }}">{{ $d['teks'] }}</a></li>
                    @endforeach
                </ol>
            </details>
        @endif

        <div class="baca-isi">
            {!! $isiHtml !!}
        </div>

        <div class="baca-alat">
            <span class="label">Bagikan:</span>
            <a class="baca-tbl wa" href="https://wa.me/?text={{ rawurlencode($judulBagikan . "\n" . $tautanTulisan) }}" target="_blank" rel="noopener" title="Bagikan ke WhatsApp" aria-label="Bagikan ke WhatsApp">
                @include('publik._ikon', ['nama' => 'wa'])
            </a>
            <a class="baca-tbl" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($tautanTulisan) }}" target="_blank" rel="noopener" title="Bagikan ke Facebook" aria-label="Bagikan ke Facebook">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h3l1-3h-4v-2c0-.6.4-1 1-1z"/></svg>
            </a>
            <a class="baca-tbl" href="https://twitter.com/intent/tweet?text={{ rawurlencode($judulBagikan) }}&amp;url={{ rawurlencode($tautanTulisan) }}" target="_blank" rel="noopener" title="Bagikan ke X" aria-label="Bagikan ke X">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 3h3l-6.6 7.6L21.5 21h-5.4l-4.2-5.5L6.9 21H3.8l7-8L2.9 3h5.5l3.9 5.2L17.5 3zm-1 16h1.7L7.6 4.7H5.8L16.5 19z"/></svg>
            </a>
            <button class="baca-tbl" type="button" id="salinTautan" title="Salin tautan" aria-label="Salin tautan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
            </button>
            <span id="salinPesan" style="font-size:.78rem;color:var(--tinta-muda);display:none">Tautan disalin ✓</span>
        </div>

        <div class="baca-cta">
            <p>Mari turut memakmurkan musholla — infaq, sedekah, dan wakaf Anda sangat berarti.</p>
            <a href="/mari-berinfaq">Salurkan Infaq</a>
        </div>
    </article>

    @if ($lain->isNotEmpty())
        <h2 class="bagian">Berita Lainnya</h2>
        <div class="jaring dua">
            @foreach ($lain as $l)
                <a class="kartu berita-kartu-tautan" href="{{ url('/berita/'.$l->slug) }}">
                    <span class="berita-gambar">
                        @if ($l->gambar_sampul)
                            <img src="{{ $l->gambar_sampul }}" alt="{{ $l->judul }}" loading="lazy">
                        @else
                            <span class="berita-gambar-kosong">@include('publik._ikon', ['nama' => 'masjid'])</span>
                        @endif
                    </span>
                    <span class="berita-isi">
                        <span class="tanggal">{{ $l->terbit_at?->translatedFormat('d F Y') }}</span>
                        <h3>{{ $l->judul }}</h3>
                        <span class="berita-ringkas">{{ \App\Support\Tulis::ringkas($l->ringkasan ?: $l->isi, 120) }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif
@endsection

@push('skrip')
<script>
    (function () {
        var batang = document.getElementById('bacaMaju');
        if (batang) {
            var hitung = function () {
                var tinggi = document.documentElement.scrollHeight - window.innerHeight;
                var posisi = tinggi > 0 ? (window.scrollY / tinggi) * 100 : 0;
                batang.style.width = Math.min(100, Math.max(0, posisi)) + '%';
            };
            window.addEventListener('scroll', hitung, { passive: true });
            window.addEventListener('resize', hitung);
            hitung();
        }

        var tombol = document.getElementById('salinTautan');
        var pesan = document.getElementById('salinPesan');
        if (tombol) {
            tombol.addEventListener('click', function () {
                var tautan = window.location.href;
                var tampil = function () {
                    if (!pesan) { return; }
                    pesan.style.display = 'inline';
                    setTimeout(function () { pesan.style.display = 'none'; }, 2000);
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(tautan).then(tampil);
                } else {
                    var sementara = document.createElement('textarea');
                    sementara.value = tautan;
                    document.body.appendChild(sementara);
                    sementara.select();
                    try { document.execCommand('copy'); tampil(); } catch (e) {}
                    document.body.removeChild(sementara);
                }
            });
        }
    })();
</script>
@endpush
