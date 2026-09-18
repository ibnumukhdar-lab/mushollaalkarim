@extends('layouts.publik')

@section('judul', ($pengaturan['nama_situs'] ?? 'Musholla Al Karim').' — '.($pengaturan['slogan'] ?? 'Kajian & Pendidikan Al-Qur\'an'))

@section('isi')
    <section class="pahlawan">
        <h1>{{ $pengaturan['nama_situs'] ?? 'Musholla Al Karim' }}</h1>
        <p>{{ $pengaturan['slogan'] ?? 'Mari makmurkan masjid — kajian rutin, pendidikan Al-Qur\'an, dan kegiatan sosial umat.' }}</p>
        <div class="aksi">
            <a class="tombol" href="/mari-berinfaq">Mari Berinfaq</a>
            <a class="tombol garis" href="/laporan-kas">Laporan Keuangan</a>
        </div>
    </section>

    @if ($berita->isNotEmpty())
        <h2 class="bagian">Kabar Terbaru</h2>
        <p class="bagian-ket">Kegiatan &amp; pengumuman terakhir dari musholla.</p>
        <div class="berita-alir" data-alir>
            <div class="berita-alir-jalur">
                @foreach ($berita as $b)
                    @php
                        $gambarSampul = $b->gambar_sampul;
                        $chipKategori = $b->kategori_utama;
                        $ringkasBerita = \App\Support\Tulis::ringkas($b->ringkasan ?: $b->isi, 120);
                    @endphp
                    <article class="berita-kartu">
                        <a class="berita-kartu-tautan" href="/berita/{{ $b->slug }}">
                            <span class="berita-gambar">
                                @if ($gambarSampul)
                                    <img src="{{ $gambarSampul }}" alt="{{ $b->judul }}" loading="lazy">
                                @else
                                    <span class="berita-gambar-kosong">@include('publik._ikon', ['nama' => 'masjid'])</span>
                                @endif
                                @if ($chipKategori)
                                    <span class="berita-chip">{{ $chipKategori }}</span>
                                @endif
                            </span>
                            <span class="berita-isi">
                                <span class="tanggal">{{ $b->terbit_at?->translatedFormat('d F Y') }}</span>
                                <h3>{{ $b->judul }}</h3>
                                @if ($ringkasBerita)
                                    <span class="berita-ringkas">{{ $ringkasBerita }}</span>
                                @endif
                            </span>
                        </a>
                    </article>
                @endforeach
            </div>
            <div class="berita-titik" data-titik aria-hidden="true"></div>
        </div>
    @endif

    @if (! empty($programHari) && $programHari['data']->isNotEmpty())
        <h2 class="bagian">Program Sepekan</h2>
        <p class="bagian-ket">Kegiatan rutin musholla setiap hari — silakan hadir dan makmurkan bersama.</p>
        <div class="jaring jadwal" style="margin-bottom:1.6rem">
            @foreach ($programHari['urut'] as $hari)
                @php
                    $daftarKegiatan = $programHari['data'][$hari] ?? collect();
                    $hariIniJuga = $hari === $programHari['hariIni'];
                @endphp
                <div @class(['kartu', 'jadwal-kartu', 'jadwal-ini' => $hariIniJuga])>
                    <div class="jadwal-kepala">
                        <h3>{{ $hari }}</h3>
                        @if ($hariIniJuga)
                            <span class="chip-hari">Hari ini</span>
                        @endif
                    </div>
                    @forelse ($daftarKegiatan as $p)
                        @php $tempatKegiatan = $p->tempat ?: null; @endphp
                        <div class="jadwal-item">
                            <span class="waktu-chip">{{ $p->waktu ?: '—' }}</span>
                            <span class="jadwal-nama">
                                {{ $p->nama }}
                                @if ($tempatKegiatan)
                                    <br><span style="font-size:.78rem;color:var(--tinta-muda)">{{ $tempatKegiatan }}</span>
                                @endif
                            </span>
                        </div>
                    @empty
                        <p class="jadwal-kosong">Belum ada kegiatan terjadwal.</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    @endif

    @if ($hal)
        <section class="kartu">
            <div class="isi-halaman">
                {!! \App\Services\BersihkanTampilan::bersihkan($hal->isi) !!}
            </div>
        </section>
    @endif

    @if ($kajian->isNotEmpty())
        <h2 class="bagian">Jadwal Kajian</h2>
        <div class="jaring dua">
            @foreach ($kajian as $k)
                <div class="kartu">
                    <h3>{{ $k->judul }}</h3>
                    <p>
                        {{ $k->tanggal?->translatedFormat('d F Y') ?? 'Rutin' }}{{ $k->waktu_mulai ? ' · '.$k->waktu_mulai : '' }}
                        @if ($k->pemateri) <br>Pemateri: {{ $k->pemateri }} @endif
                        @if ($k->tempat) <br>Tempat: {{ $k->tempat }} @endif
                    </p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($wakaf->isNotEmpty())
        <h2 class="bagian">Program & Wakaf</h2>
        <div class="jaring dua">
            @foreach ($wakaf as $w)
                <div class="kartu">
                    <h3>{{ $w->nama }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $w->keterangan), 160) }}</p>
                    @if ($w->target > 0)
                        <p>
                            Terkumpul <strong>Rp{{ number_format((float) $w->terkumpul, 0, ',', '.') }}</strong>
                            dari Rp{{ number_format((float) $w->target, 0, ',', '.') }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection

@push('skrip')
    <script>
        // Kabar terbaru: alir otomatis di layar kecil, grid 3 kolom di layar lebar
        document.querySelectorAll('[data-alir]').forEach(function (wadah) {
            var jalur = wadah.querySelector('.berita-alir-jalur');
            var titik = wadah.querySelector('[data-titik]');
            if (!jalur) return;
            var kartu = Array.prototype.slice.call(jalur.querySelectorAll('.berita-kartu'));
            if (kartu.length < 2) return;

            function posisiKartu(i) {
                return kartu[i].offsetLeft - kartu[0].offsetLeft;
            }
            function terdekat() {
                var pos = jalur.scrollLeft, pilih = 0, jarak = Infinity;
                kartu.forEach(function (k, i) {
                    var d = Math.abs(posisiKartu(i) - pos);
                    if (d < jarak) { jarak = d; pilih = i; }
                });
                return pilih;
            }
            function tandai() {
                if (!titik) return;
                var aktif = terdekat();
                titik.querySelectorAll('button').forEach(function (b, i) { b.classList.toggle('aktif', i === aktif); });
            }

            if (titik) {
                kartu.forEach(function (k, i) {
                    var t = document.createElement('button');
                    t.type = 'button';
                    t.setAttribute('aria-label', 'Ke kabar ' + (i + 1));
                    t.addEventListener('click', function () { jalur.scrollTo({ left: posisiKartu(i), behavior: 'smooth' }); });
                    titik.appendChild(t);
                });
            }

            jalur.addEventListener('scroll', tandai);
            tandai();

            var modeAlir = function () { return window.matchMedia('(max-width: 899px)').matches; };
            var tahan = false;

            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                setInterval(function () {
                    if (!modeAlir() || tahan) return;
                    var berikut = (terdekat() + 1) % kartu.length;
                    jalur.scrollTo({ left: posisiKartu(berikut), behavior: 'smooth' });
                    tandai();
                }, 5000);
            }

            ['pointerdown', 'touchstart', 'mouseenter'].forEach(function (ev) {
                jalur.addEventListener(ev, function () { tahan = true; });
            });
            ['pointerup', 'touchend', 'mouseleave'].forEach(function (ev) {
                jalur.addEventListener(ev, function () { window.setTimeout(function () { tahan = false; }, 1500); });
            });
        });
    </script>
@endpush
