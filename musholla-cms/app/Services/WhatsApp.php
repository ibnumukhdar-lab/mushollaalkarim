<?php

namespace App\Services;

use App\Models\WaBroadcast;
use App\Models\WaPesan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mesin WhatsApp Musholla Al Karim.
 *
 * Penerima diambil dari data yang SUDAH ada (donatur, orang tua santri,
 * ustadz, pengurus). Isi pesan mendukung variabel {{...}} yang diisi otomatis
 * per penerima.
 *
 * Dua cara kirim:
 *  - manual  : aplikasi menyiapkan tautan wa.me; pengurus menekan kirim
 *  - otomatis: lewat gateway Dripsender (Pengaturan → URL + token)
 */
class WhatsApp
{
    /** Pengaturan inti WA Auto. Sengaja hanya tiga ini. */
    public const BAWAAN = [
        'wa_auto_aktif' => '0',
        'wa_gateway_url' => 'https://api.dripsender.id/send',
        'wa_gateway_token' => '',
        'wa_gateway_jeda' => '3',
        'wa_notif_nomor' => '',
    ];

    /** Penerima per kelompok: [nama, nomor, data]. */
    public static function penerima(string $grup): Collection
    {
        return match ($grup) {
            'subscriber' => self::dariSubscriber(),
            'admin', 'pengurus' => self::dariAdmin(),
            'semua' => self::dariSubscriber()->merge(self::dariAdmin())
                ->unique(fn ($p) => $p['nomor'])->values(),
            // DONATUR — hanya yang tercatat di tabel donatur (dipakai laporan kas bulanan)
            'donatur' => self::dariDonatur(),
            // nama kelompok lama tetap diterima agar tautan/data lama tidak error
            'pengguna', 'ustadz', 'ortu' => self::dariSubscriber(),
            default => collect(),
        };
    }

    /**
     * SUBSCRIBERS — satu daftar untuk semua kontak yang bisa dikirimi kabar:
     * akun pendaftar situs (/daftar), donatur, ustadz, dan orang tua santri.
     * Nomor milik akun admin dikeluarkan supaya tidak dobel dengan kelompok Admin.
     */
    private static function dariSubscriber(): Collection
    {
        $nomorAdmin = self::nomorAdmin();

        $kumpul = collect()
            ->merge(self::dariTabelPengguna(['subscriber', 'ortu', 'anggota']))
            ->merge(self::dariDonatur())
            ->merge(self::dariUstadz())
            ->merge(self::dariOrangTuaSantri());

        return $kumpul->filter()
            ->reject(fn ($p) => in_array($p['nomor'], $nomorAdmin, true))
            ->unique(fn ($p) => $p['nomor'])
            ->values();
    }

    /** ADMIN — akun pengelola (peran admin) yang punya nomor WhatsApp. */
    private static function dariAdmin(): Collection
    {
        return self::dariTabelPengguna(['admin']);
    }

    /** Nomor milik akun admin (untuk mencegah dobel dengan kelompok Subscribers). */
    private static function nomorAdmin(): array
    {
        return DB::table('users')->where('peran', 'admin')->where('aktif', true)
            ->whereNotNull('no_wa')->where('no_wa', '!=', '')
            ->pluck('no_wa')->map(fn ($n) => self::nomorBersih($n))->all();
    }

    /** Ambil penerima dari tabel users berdasarkan peran. */
    private static function dariTabelPengguna(array $peran): Collection
    {
        return DB::table('users')
            ->whereIn('peran', $peran)
            ->where('aktif', true)
            ->whereNotNull('no_wa')->where('no_wa', '!=', '')
            ->orderBy('nama_lengkap')
            ->get(['id', 'name', 'nama_lengkap', 'no_wa', 'peran', 'email'])
            ->map(fn ($u) => self::rapikan($u->nama_lengkap ?: $u->name, $u->no_wa, [
                'peran' => $u->peran, 'email' => $u->email, 'jenis' => 'pengguna',
            ]))
            ->filter()
            ->unique(fn ($p) => $p['nomor'])
            ->values();
    }

    private static function dariDonatur(): Collection
    {
        return DB::table('donatur')->where('aktif', true)->whereNotNull('no_wa')->where('no_wa', '!=', '')
            ->orderBy('nama')->get(['id', 'nama', 'no_wa', 'kategori'])
            ->map(fn ($d) => self::rapikan($d->nama, $d->no_wa, ['kategori' => $d->kategori, 'jenis' => 'donatur']))
            ->filter();
    }

    private static function dariOrangTuaSantri(): Collection
    {
        $keluar = collect();

        $santri = DB::table('santri')->whereNotNull('ortu_user_id')->get(['nama', 'ortu_user_id']);
        if ($santri->isNotEmpty()) {
            $users = DB::table('users')->whereIn('id', $santri->pluck('ortu_user_id'))
                ->get(['id', 'name', 'nama_lengkap', 'no_wa'])->keyBy('id');
            foreach ($santri as $s) {
                $u = $users[$s->ortu_user_id] ?? null;
                if ($u && $u->no_wa) {
                    $keluar->push(self::rapikan($u->nama_lengkap ?: $u->name, $u->no_wa, ['santri' => $s->nama, 'jenis' => 'ortu']));
                }
            }
        }

        // orang tua tanpa akun: nomor tersimpan di catatan pendaftaran ("WA: 08xx")
        foreach (DB::table('santri')->whereNull('ortu_user_id')->whereNotNull('catatan')->get(['nama', 'catatan']) as $s) {
            if (preg_match('~WA:\s*([0-9+()\s-]{8,})~i', (string) $s->catatan, $m)) {
                $keluar->push(self::rapikan('Orang tua '.$s->nama, $m[1], ['santri' => $s->nama, 'jenis' => 'ortu']));
            }
        }

        return $keluar->filter()->unique(fn ($p) => $p['nomor'])->values();
    }

    private static function dariUstadz(): Collection
    {
        return DB::table('ustadz')->where('aktif', true)->whereNotNull('no_wa')->where('no_wa', '!=', '')
            ->orderBy('nama')->get(['nama', 'no_wa', 'bidang'])
            ->map(fn ($u) => self::rapikan($u->nama, $u->no_wa, ['bidang' => $u->bidang, 'jenis' => 'ustadz']))
            ->filter();
    }

    /** Susun satu penerima; null bila nomornya tidak masuk akal. */
    private static function rapikan(?string $nama, ?string $nomor, array $data = []): ?array
    {
        $bersih = self::nomorBersih($nomor);
        if (strlen($bersih) < 9) {
            return null;
        }

        return ['nama' => trim((string) $nama) ?: 'Tanpa nama', 'nomor' => $bersih, 'data' => $data];
    }

    /** 0812… / +62 812… / 62-812… → 62812… */
    public static function nomorBersih(?string $nomor): string
    {
        $angka = preg_replace('~[^0-9]~', '', (string) $nomor) ?? '';
        if (str_starts_with($angka, '0')) {
            $angka = '62'.substr($angka, 1);
        } elseif (str_starts_with($angka, '8')) {
            $angka = '62'.$angka;
        }

        return $angka;
    }

    /** Nilai variabel standar yang bisa dipakai di pesan. */
    public static function variabelStandar(): array
    {
        $masuk = (float) DB::table('kas')->where('jenis', 'masuk')->sum('jumlah');
        $keluar = (float) DB::table('kas')->where('jenis', 'keluar')->sum('jumlah');

        $awalBulan = now()->startOfMonth();
        $awalLalu = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $akhirLalu = now()->copy()->subMonthNoOverflow()->endOfMonth();

        $rp = fn ($angka) => 'Rp '.number_format((float) $angka, 0, ',', '.');
        $jumlahKas = function ($jenis, $dari, $sampai) {
            return (float) DB::table('kas')->where('jenis', $jenis)
                ->where('tanggal', '>=', $dari)->where('tanggal', '<=', $sampai)->sum('jumlah');
        };

        $masukBulan = $jumlahKas('masuk', $awalBulan->toDateString(), now()->toDateString());
        $keluarBulan = $jumlahKas('keluar', $awalBulan->toDateString(), now()->toDateString());
        $masukLalu = $jumlahKas('masuk', $awalLalu->toDateString(), $akhirLalu->toDateString());
        $keluarLalu = $jumlahKas('keluar', $awalLalu->toDateString(), $akhirLalu->toDateString());

        $kajian = DB::table('kajian')->where('aktif', true)->whereNotNull('tanggal')
            ->where('tanggal', '>=', now()->toDateString())->orderBy('tanggal')->first();

        $pengaturan = DB::table('pengaturan')->pluck('nilai', 'kunci');

        $saldo = $rp($masuk - $keluar);

        return [
            // identitas
            'nama_musholla' => $pengaturan['nama_situs'] ?? 'Musholla Al Karim',
            'slogan' => $pengaturan['slogan'] ?? '',
            'tautan_situs' => url('/'),

            // waktu
            'tanggal' => now()->translatedFormat('j F Y'),
            'hari' => now()->translatedFormat('l'),
            'bulan' => now()->translatedFormat('F'),
            'bulan_tahun' => now()->translatedFormat('F Y'),
            'tahun' => now()->translatedFormat('Y'),
            'jam' => now()->format('H:i'),

            // keuangan (beberapa nama agar mudah diingat)
            'saldo_terkini' => $saldo,
            'saldo' => $saldo,
            'masuk_bulan_ini' => $rp($masukBulan),
            'keluar_bulan_ini' => $rp($keluarBulan),
            'pemasukan_bulan' => $rp($masukBulan),
            'pengeluaran_bulan' => $rp($keluarBulan),
            'selisih_bulan_ini' => $rp($masukBulan - $keluarBulan),
            'masuk_bulan_lalu' => $rp($masukLalu),
            'keluar_bulan_lalu' => $rp($keluarLalu),
            'masuk_total' => $rp($masuk),
            'keluar_total' => $rp($keluar),

            // jamaah
            'jumlah_donatur' => (string) DB::table('donatur')->where('aktif', true)->count(),
            'jumlah_santri' => (string) DB::table('santri')->where('aktif', true)->count(),
            'jumlah_ustadz' => (string) DB::table('ustadz')->where('aktif', true)->count(),

            // kajian berikutnya
            'judul_kajian' => $kajian->judul ?? '-',
            'pemateri' => $kajian->pemateri ?? '-',
            'tanggal_kajian' => isset($kajian->tanggal) ? \Illuminate\Support\Carbon::parse($kajian->tanggal)->translatedFormat('j F Y') : '-',
        ];
    }

    /**
     * Variabel untuk LAPORAN SATU BULAN tertentu (bukan selalu bulan berjalan).
     *
     * Dipakai tombol "Kirim laporan kas" di halaman Kas: ketika sudah masuk Oktober,
     * pengurus tetap bisa mengirim laporan September dengan angka September
     * (pemasukan, pengeluaran, dan saldo AKHIR bulan itu). Bulan yang sudah ditutup
     * dipakai apa adanya dari tabel kas_bulan sehingga laporan lama tidak berubah.
     */
    public static function variabelBulan(?string $periode): array
    {
        $standar = self::variabelStandar();

        if (! $periode || ! preg_match('~^\d{4}-\d{2}$~', $periode)) {
            return $standar;
        }

        $rantai = \App\Support\KasBulanan::rantai();
        $waktu = \Illuminate\Support\Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
        $rp = fn ($angka) => 'Rp '.number_format((float) $angka, 0, ',', '.');

        $baris = $rantai[$periode] ?? null;
        if (! $baris) {
            $jumlah = \App\Support\KasBulanan::jumlahBulan($periode);
            $baris = [
                'saldo_awal' => 0.0,
                'masuk' => (float) $jumlah['masuk'],
                'keluar' => (float) $jumlah['keluar'],
                'saldo_akhir' => (float) $jumlah['masuk'] - (float) $jumlah['keluar'],
                'ditutup' => false,
                'ditutup_at' => null,
                'label' => $waktu->translatedFormat('F Y'),
            ];
        }

        // total kumulatif SAMPAI akhir bulan itu (bukan sampai hari ini)
        $masukTotal = 0.0;
        $keluarTotal = 0.0;
        foreach ($rantai as $p => $b) {
            if ($p > $periode) {
                break;
            }
            $masukTotal += (float) $b['masuk'];
            $keluarTotal += (float) $b['keluar'];
        }

        $jumlahCatatan = (int) \App\Models\Kas::query()
            ->whereYear('tanggal', $waktu->year)->whereMonth('tanggal', $waktu->month)->count();

        $nilai = array_merge($standar, [
            'bulan' => $waktu->translatedFormat('F'),
            'bulan_tahun' => $waktu->translatedFormat('F Y'),
            'bulan_laporan' => $waktu->translatedFormat('F Y'),
            'tahun' => $waktu->format('Y'),
            'periode_bulan' => $periode,
            'saldo_awal' => $rp($baris['saldo_awal']),
            'masuk_bulan_ini' => $rp($baris['masuk']),
            'keluar_bulan_ini' => $rp($baris['keluar']),
            'pemasukan_bulan' => $rp($baris['masuk']),
            'pengeluaran_bulan' => $rp($baris['keluar']),
            'selisih_bulan_ini' => $rp((float) $baris['masuk'] - (float) $baris['keluar']),
            'saldo_akhir' => $rp($baris['saldo_akhir']),
            // laporan bulan tertentu: "kas" yang dilaporkan = saldo akhir bulan itu
            'saldo_terkini' => $rp($baris['saldo_akhir']),
            'saldo' => $rp($baris['saldo_akhir']),
            'saldo_terkini_hari_ini' => (string) $standar['saldo_terkini'],
            'masuk_total' => $rp($masukTotal),
            'keluar_total' => $rp($keluarTotal),
            'jumlah_catatan' => (string) $jumlahCatatan,
            'status_bulan' => $baris['ditutup']
                ? 'sudah ditutup'.(empty($baris['ditutup_at']) ? '' : ' '.$baris['ditutup_at']->translatedFormat('j F Y'))
                : 'masih berjalan',
        ]);

        // "bulan lalu" pada laporan = bulan sebelum bulan yang dilaporkan
        $sebelum = $waktu->copy()->subMonthNoOverflow()->format('Y-m');
        if (isset($rantai[$sebelum])) {
            $nilai['masuk_bulan_lalu'] = $rp($rantai[$sebelum]['masuk']);
            $nilai['keluar_bulan_lalu'] = $rp($rantai[$sebelum]['keluar']);
        }

        return $nilai;
    }

    /**
     * Draf teks laporan kas untuk satu bulan: isi Template Pesan "Laporan Kas Bulanan"
     * (atau aturan kas_bulanan) dengan penyebutan bulan yang jelas, siap disunting
     * pengurus sebelum dikirim ke donatur.
     */
    public static function drafLaporanKas(string $periode): string
    {
        $dasar = (string) (
            DB::table('wa_template')->where('judul', 'like', '%kas%')->where('aktif', true)->orderBy('id')->value('isi')
            ?: DB::table('wa_aturan')->where('kunci', 'kas_bulanan')->value('isi')
            ?: implode("\n", [
                'Assalamualaikum Warahmatullahi Wabarakatuh.',
                'Kepada Bapak/Ibu {{nama_donatur}}',
                '',
                'Berikut ini Laporan Kas Bulan {{bulan}} {{tahun}}:',
                'Saldo awal: {{saldo_awal}}',
                'Pemasukan: {{pemasukan_bulan}}',
                'Pengeluaran: {{pengeluaran_bulan}}',
                'Saldo akhir: {{saldo_akhir}}',
                '',
                'Rincian: {{tautan_situs}}/laporan-kas',
                '',
                'Fahrizal, M.Pd',
                'Sekretaris',
            ])
        );

        // sebutkan bulannya supaya laporan bulan lalu tidak terbaca sebagai "bulan ini"
        $dasar = str_replace('Laporan Kas Bulan ini', 'Laporan Kas Bulan {{bulan}} {{tahun}}', $dasar);
        $dasar = str_replace('Kas: {{saldo_terkini}}', 'Saldo akhir {{bulan}} {{tahun}}: {{saldo_akhir}}', $dasar);

        return $dasar;
    }

    /**
     * Daftar variabel yang dikenali: standar + per penerima + untuk tulisan.
     * Dipakai untuk panduan di panel dan untuk memeriksa variabel yang salah tulis.
     */
    public static function variabelDikenal(): array
    {
        $standar = array_keys(self::variabelStandar());
        // variabel tambahan yang hanya muncul pada LAPORAN SATU BULAN terpilih
        $standar = array_merge($standar, [
            'saldo_awal', 'saldo_akhir', 'saldo_terkini_hari_ini', 'jumlah_catatan',
            'status_bulan', 'periode_bulan', 'bulan_laporan',
        ]);
        $perPenerima = ['nama', 'nama_penerima', 'nama_donatur', 'nama_ortu', 'nama_santri', 'nominal', 'tujuan', 'kategori', 'bidang'];
        $untukTulisan = ['judul_berita', 'ringkasan_berita', 'tautan_berita', 'kategori_berita'];

        return array_values(array_unique(array_merge($standar, $perPenerima, $untukTulisan)));
    }

    /** Variabel {{...}} di dalam teks yang tidak dikenal (indikasi salah tulis). */
    public static function variabelTidakDikenal(?string $teks): array
    {
        preg_match_all('~\{\{\s*([a-zA-Z0-9_]+)\s*\}\}~', (string) $teks, $m);
        $dikenal = self::variabelDikenal();

        return array_values(array_unique(array_diff($m[1] ?? [], $dikenal)));
    }

    /**
     * Rapikan teks agar enak dibaca di WhatsApp: entitas HTML diurai, spasi ganda
     * dan baris kosong berlebih dibuang, penanda markdown diubah ke gaya WhatsApp.
     * Dipakai untuk SEMUA teks yang berangkat ke WA (termasuk tulisan pengurus).
     */
    public static function rapikanTeks(?string $teks): string
    {
        $t = (string) $teks;

        // entitas HTML → huruf biasa (&amp; &nbsp; &#8217; …)
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // tag HTML → baris
        $t = preg_replace('~<br\s*/?>~i', "\n", $t);
        $t = preg_replace('~</(p|div|li|h[1-6])>~i', "\n", $t);
        $t = preg_replace('~<li[^>]*>~i', '• ', $t);
        $t = strip_tags($t);

        // penanda markdown → gaya WhatsApp
        $t = preg_replace('~\[([^\]]+)\]\(([^)]+)\)~', '$1 ($2)', $t);   // [teks](tautan)
        $t = preg_replace('~\*\*([^*]+)\*\*~', '*$1*', $t);              // **tebal** → *tebal*
        $t = preg_replace('~^\s*#{1,6}\s*(.+)$~m', '*$1*', $t);            // ## judul → *judul*
        $t = preg_replace('~^\s*[-*+]\s+~m', '• ', $t);                    // - butir → • butir
        $t = preg_replace('~^\s*---+\s*$~m', '', $t);                      // garis pemisah

        // spasi & baris
        $t = str_replace(["\u{00A0}", "\u{200B}", "\u{FEFF}"], ' ', $t);
        $t = preg_replace('~[ \t]+~u', ' ', $t);            // spasi/tab ganda
        $t = preg_replace('~ *\n *~', "\n", $t);           // spasi di ujung baris
        $t = preg_replace('~\n{3,}~', "\n\n", $t);        // maksimal satu baris kosong
        $t = preg_replace('~\s+:\s+~', ': ', $t);          // "Mimbar : Baca" → "Mimbar: Baca"
        $t = preg_replace('~\s+([,.!?;:])~u', '$1', $t);    // spasi sebelum tanda baca
        $t = preg_replace('~\v~u', "\n", $t);

        return trim($t);
    }

    /** Potong teks pada batas kata (bukan di tengah kata) dengan tanda … */
    public static function potongRapi(?string $teks, int $batas = 200): string
    {
        $t = self::rapikanTeks($teks);
        if (mb_strlen($t) <= $batas) {
            return $t;
        }

        $potong = mb_substr($t, 0, $batas);
        $spasi = mb_strrpos($potong, ' ');
        if ($spasi !== false && $spasi > $batas * 0.6) {
            $potong = mb_substr($potong, 0, $spasi);
        }

        return rtrim($potong, " \t.,;:—-").'…';
    }

    /** Rapikan penulisan waktu: "17:30 WIB - 18:30 WIB" → "17:30–18:30 WIB". */
    public static function rapikanWaktu(?string $waktu): string
    {
        $w = self::rapikanTeks($waktu);
        $w = preg_replace('~\s*(?:-|–|—|s/d|s\.d\.)\s*~i', '–', $w);
        $w = preg_replace('~(\d{1,2}:\d{2})[–]?(\d{1,2}:\d{2})~', '$1–$2', $w);
        $w = preg_replace('~[–]\s*WIB\s*[–]~', '–', $w);   // "17:30 WIB–18:30" → "17:30–18:30"
        $w = preg_replace('~\s*–\s*~', '–', $w);
        $w = preg_replace('~\s{2,}~', ' ', $w);

        return trim($w);
    }

    /**
     * Teks agenda satu hari untuk dibagikan ke WhatsApp — berbutir, satu kegiatan
     * per baris, tautan di baris sendiri supaya tidak menempel ke kegiatan terakhir.
     */
    public static function teksAgenda(string $hari, $kegiatan, string $namaSitus, string $tautan): string
    {
        $baris = ['*Agenda '.$hari.' — '.self::rapikanTeks($namaSitus).'*', ''];

        if (! $kegiatan || (is_countable($kegiatan) && count($kegiatan) === 0)) {
            $baris[] = 'Belum ada kegiatan terjadwal untuk '.$hari.'.';
        } else {
            foreach ($kegiatan as $k) {
                $waktu = self::rapikanWaktu($k->waktu ?? null);
                $nama = self::rapikanTeks($k->nama ?? '');
                $tempat = self::rapikanTeks($k->tempat ?? '');
                $utuh = trim(($waktu !== '' ? $waktu.' — ' : '').$nama.($tempat !== '' ? ' ('.$tempat.')' : ''));
                $baris[] = '• '.$utuh;
            }
        }

        $baris[] = '';
        $baris[] = 'Jadwal lengkap: '.$tautan;

        return self::rapikanTeks(implode("\n", $baris));
    }

    /** Isi variabel {{...}} pada teks. Variabel tak dikenal dibiarkan apa adanya (tidak error). */
    public static function isiVariabel(string $teks, array $data): string
    {
        $semua = array_merge(self::variabelStandar(), $data);
        foreach ($semua as $kunci => $nilai) {
            if ($nilai === null || is_array($nilai)) {
                continue;
            }
            $teks = str_replace(['{{'.$kunci.'}}', '{{ '.$kunci.' }}'], (string) $nilai, $teks);
        }

        return $teks;
    }

    /** Tautan WhatsApp siap tekan (mode manual). */
    public static function tautan(string $nomor, string $pesan): string
    {
        return 'https://wa.me/'.self::nomorBersih($nomor).'?text='.rawurlencode($pesan);
    }

    /** Pengaturan WA Auto. */
    public static function pengaturanGateway(): array
    {
        $tersimpan = DB::table('pengaturan')->whereIn('kunci', array_keys(self::BAWAAN))->pluck('nilai', 'kunci')->all();

        return array_merge(self::BAWAAN, array_map(fn ($v) => (string) $v, $tersimpan));
    }

    /** Apakah WA Auto siap dipakai? */
    public static function gatewaySiap(): bool
    {
        $p = self::pengaturanGateway();

        return $p['wa_auto_aktif'] === '1' && $p['wa_gateway_url'] !== '' && $p['wa_gateway_token'] !== '';
    }

    /**
     * Kirim satu pesan lewat Dripsender.
     * Balasan: [berhasil(bool), galat(?string), balasan(?string)]
     */
    public static function kirimGateway(string $nomor, string $pesan): array
    {
        $p = self::pengaturanGateway();

        if ($p['wa_gateway_url'] === '' || $p['wa_gateway_token'] === '') {
            return [false, 'URL atau token gateway belum diisi.', null];
        }

        try {
            $balasan = Http::asJson()->timeout(25)->post($p['wa_gateway_url'], [
                'api_key' => $p['wa_gateway_token'],
                'phone' => self::nomorBersih($nomor),
                'text' => $pesan,
            ]);

            $cuplikan = mb_substr(trim(strip_tags((string) $balasan->body())), 0, 220);

            if ($balasan->successful()) {
                return [true, null, $cuplikan];
            }

            return [false, 'Gateway menolak (HTTP '.$balasan->status().'). '.$cuplikan, $cuplikan];
        } catch (\Throwable $e) {
            Log::warning('WA gateway gagal: '.$e->getMessage());

            return [false, 'Gagal menghubungi gateway: '.mb_substr($e->getMessage(), 0, 160), null];
        }
    }

    /** Kirim satu pesan uji (tombol "Uji kirim" di Pengaturan). */
    public static function ujiKirim(string $nomor, string $pesan): array
    {
        [$ok, $galat, $balasan] = self::kirimGateway($nomor, $pesan);

        return ['berhasil' => $ok, 'galat' => $galat, 'balasan' => $balasan, 'nomor' => self::nomorBersih($nomor)];
    }

    /**
     * Buat kampanye baru: satu baris kampanye + satu baris antrean per penerima
     * (pesan sudah diisi variabel masing-masing).
     */
    public static function buatKampanye(array $data, Collection $penerima, array $tambahan = []): WaBroadcast
    {
        return DB::transaction(function () use ($data, $penerima, $tambahan) {
            $kampanye = WaBroadcast::query()->create([
                'judul' => $data['judul'] ?? 'Pesan '.now()->translatedFormat('j F Y H:i'),
                'isi' => $data['isi'] ?? '',
                'grup' => $data['grup'] ?? null,
                'mode' => in_array($data['mode'] ?? 'manual', ['manual', 'gateway'], true) ? $data['mode'] : 'manual',
                'wa_template_id' => $data['wa_template_id'] ?? null,
                'jumlah_target' => $penerima->count(),
                'terkirim' => 0,
                'gagal' => 0,
                'status' => 'berjalan',
                'mulai_at' => now(),
                'oleh_user_id' => auth()->id(),
            ]);

            foreach ($penerima as $orang) {
                WaPesan::query()->create([
                    'wa_broadcast_id' => $kampanye->id,
                    'nama' => $orang['nama'],
                    'nomor' => $orang['nomor'],
                    'pesan' => self::rapikanTeks(self::isiVariabel((string) ($data['isi'] ?? ''), array_merge(self::variabelStandar(), $tambahan, $orang['data'], [
                        'nama' => $orang['nama'], 'nama_penerima' => $orang['nama'],
                        'nama_donatur' => $orang['nama'], 'nama_ortu' => $orang['nama'],
                    ]))),
                    'status' => 'menunggu',
                    'via' => ($data['mode'] ?? 'manual') === 'gateway' ? 'gateway' : 'manual',
                ]);
            }

            return $kampanye;
        });
    }

    /** Proses antrean kampanye bermode otomatis (jeda bisa ditiadakan saat dipanggil dari web). */
    public static function prosesAntrean(WaBroadcast $kampanye, ?int $jeda = null): array
    {
        $terkirim = 0;
        $gagal = 0;
        $jeda ??= max(0, (int) self::pengaturanGateway()['wa_gateway_jeda']);

        $antre = $kampanye->pesan()->where('status', 'menunggu')->get();

        foreach ($antre as $urutan => $pesan) {
            [$ok, $galat] = self::kirimGateway($pesan->nomor, (string) $pesan->pesan);
            $pesan->update([
                'status' => $ok ? 'terkirim' : 'gagal',
                'dikirim_at' => $ok ? now() : null,
                'galat' => $galat,
            ]);
            $ok ? $terkirim++ : $gagal++;

            // jeda supaya nomor tidak dianggap spam oleh WhatsApp
            if ($jeda > 0 && $urutan < $antre->count() - 1) {
                sleep($jeda);
            }
        }

        $kampanye->refresh();
        $kampanye->update([
            'terkirim' => $kampanye->pesan()->where('status', 'terkirim')->count(),
            'gagal' => $kampanye->pesan()->where('status', 'gagal')->count(),
            'status' => $kampanye->pesan()->where('status', 'menunggu')->exists() ? 'berjalan' : 'selesai',
            'selesai_at' => $kampanye->pesan()->where('status', 'menunggu')->exists() ? null : now(),
        ]);

        return ['terkirim' => $terkirim, 'gagal' => $gagal];
    }

    /**
     * Notifikasi otomatis dari kejadian aplikasi. Pesan masuk ANTREAN (mode
     * manual) atau langsung dikirim (WA Auto aktif) — tidak ada yang hilang.
     */
    public static function notifikasi(string $kunci, array $data, ?string $nomorTujuan = null, ?string $namaTujuan = null): ?WaPesan
    {
        $aturan = DB::table('wa_aturan')->where('kunci', $kunci)->where('aktif', true)->first();
        if (! $aturan || ! $aturan->isi) {
            return null;
        }

        $tujuan = $nomorTujuan ?: self::nomorPengurus();
        if (! $tujuan) {
            return null;
        }

        $pesan = self::isiVariabel($aturan->isi, array_merge(self::variabelStandar(), $data));

        $baris = WaPesan::query()->create([
            'wa_broadcast_id' => null,
            'nama' => $namaTujuan ?: 'Pengurus',
            'nomor' => self::nomorBersih($tujuan),
            'pesan' => $pesan,
            'status' => 'menunggu',
            'via' => self::gatewaySiap() ? 'otomatis' : 'manual',
        ]);

        if (self::gatewaySiap() && $aturan->penerima === 'otomatis') {
            [$ok, $galat] = self::kirimGateway($baris->nomor, $pesan);
            $baris->update([
                'status' => $ok ? 'terkirim' : 'gagal',
                'dikirim_at' => $ok ? now() : null,
                'galat' => $galat,
            ]);
        }

        return $baris->refresh();
    }

    /**
     * Sebar kabar tulisan baru ke kontak WhatsApp (dipakai centang "Kirim update
     * ke kontak WA" saat menyimpan tulisan). Pesannya diambil dari aturan
     * "berita_baru" supaya mudah diubah di panel.
     */
    public static function siarkanBerita($berita, array $grup = ['donatur']): array
    {
        $grup = array_values(array_filter(array_map('strval', $grup)));
        $grup = in_array('semua', $grup, true) ? ['subscriber', 'admin'] : $grup;
        if (empty($grup)) {
            $grup = ['donatur'];
        }

        $penerima = collect();
        foreach ($grup as $g) {
            $penerima = $penerima->merge(self::penerima($g));
        }
        $penerima = $penerima->unique(fn ($p) => $p['nomor'])->values();

        if ($penerima->isEmpty()) {
            return ['jumlah' => 0, 'terkirim' => 0, 'menunggu' => 0, 'gagal' => 0,
                'ringkas' => 'Tulisan tersimpan. Tidak ada nomor WhatsApp pada kelompok '.implode(', ', $grup).' — tidak ada pesan dibuat.'];
        }

        // pesan diambil dari aturan berita_baru (bisa diubah pengurus di panel)
        $aturan = DB::table('wa_aturan')->where('kunci', 'berita_baru')->first();
        $isi = $aturan->isi ?? self::pesanTulisanBawaan();

        $tautan = url('/berita/'.$berita->slug);
        $kampanye = self::buatKampanye([
            'judul' => 'Update tulisan: '.mb_substr((string) $berita->judul, 0, 80),
            'isi' => $isi,
            'grup' => implode(',', $grup),
            'mode' => self::gatewaySiap() ? 'gateway' : 'manual',
        ], $penerima, [
            'judul_berita' => (string) $berita->judul,
            'ringkasan_berita' => self::potongRapi(\App\Support\Tulis::ringkas($berita->ringkasan ?: $berita->isi, 400), 200),
            'tautan_berita' => $tautan,
            'tautan' => $tautan,
            'kategori_berita' => (string) ($berita->kategori_utama ?? ''),
        ]);

        $terkirim = 0;
        $gagal = 0;
        if (self::gatewaySiap()) {
            $hasil = self::prosesAntrean($kampanye, 0);   // tanpa jeda: dipanggil dari permintaan web
            $terkirim = $hasil['terkirim'];
            $gagal = $hasil['gagal'];
        }

        $menunggu = $kampanye->pesan()->where('status', 'menunggu')->count();

        $ringkas = 'Update WA: '.$terkirim.' terkirim'
            .($menunggu ? ', '.$menunggu.' menunggu di antrean' : '')
            .($gagal ? ', '.$gagal.' gagal' : '')
            .' ('.$penerima->count().' kontak: '.implode(', ', $grup).').'
            .($terkirim === 0 && $menunggu > 0 ? ' Buka Pusat WhatsApp → Antrean untuk mengirim.' : '');

        return ['jumlah' => $penerima->count(), 'terkirim' => $terkirim, 'menunggu' => $menunggu, 'gagal' => $gagal, 'ringkas' => $ringkas];
    }

    /** Pesan bawaan bila pengurus belum pernah menyimpan aturan berita_baru. */
    public static function pesanTulisanBawaan(): string
    {
        return "Assalamualaikum Wr. Wb.\n\n"
            ."{{nama_musholla}} baru saja menerbitkan kabar baru:\n\n"
            ."*{{judul_berita}}*\n\n"
            ."{{ringkasan_berita}}\n\n"
            ."Selengkapnya: {{tautan_berita}}\n\n"
            ."Semoga bermanfaat. Jazakumullahu khairan.";
    }

    /** Nomor pengurus untuk notifikasi internal. */
    public static function nomorPengurus(): ?string
    {
        $dariPengaturan = trim((string) self::pengaturanGateway()['wa_notif_nomor']);
        if ($dariPengaturan !== '') {
            return self::nomorBersih($dariPengaturan);
        }

        $nomor = DB::table('users')->where('peran', 'admin')->whereNotNull('no_wa')->where('no_wa', '!=', '')
            ->orderBy('id')->value('no_wa');

        return $nomor ? self::nomorBersih($nomor) : null;
    }

    /** Ringkasan antrean untuk dasbor. */
    public static function ringkasan(): array
    {
        return [
            'menunggu' => WaPesan::query()->where('status', 'menunggu')->count(),
            'terkirim' => WaPesan::query()->where('status', 'terkirim')->count(),
            'gagal' => WaPesan::query()->where('status', 'gagal')->count(),
        ];
    }
}
