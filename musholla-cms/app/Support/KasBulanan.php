<?php

namespace App\Support;

use App\Models\Kas;
use App\Models\KasBulan;
use Illuminate\Support\Carbon;

/**
 * Rekap kas BULANAN + tutup kas.
 *
 * Aturan yang dipegang:
 *  - Saldo bersambung: saldo awal sebuah bulan = saldo akhir bulan sebelumnya.
 *    Karena itu sisa uang bulan lalu otomatis menjadi saldo masuk bulan baru.
 *  - Bulan yang sudah DITUTUP angkanya dibekukan (dipakai apa adanya dari tabel kas_bulan),
 *    sehingga laporan bulan lalu tidak berubah kalau ada catatan baru di bulan lain.
 *  - Perhitungan sengaja dibuat di PHP (bukan DATE_FORMAT) supaya sama hasilnya di MySQL
 *    produksi maupun SQLite saat mengembangkan di PC.
 */
class KasBulanan
{
    /** Jumlah masuk/keluar tiap bulan: ['2026-09' => ['masuk' => 350000.0, 'keluar' => 69000.0]] */
    public static function jumlahPerBulan(): array
    {
        $hasil = [];

        foreach (Kas::query()->select('tanggal', 'jenis', 'jumlah')->orderBy('tanggal')->cursor() as $baris) {
            if (! $baris->tanggal) {
                continue;
            }
            $periode = $baris->tanggal->format('Y-m');
            $hasil[$periode]['masuk'] = $hasil[$periode]['masuk'] ?? 0.0;
            $hasil[$periode]['keluar'] = $hasil[$periode]['keluar'] ?? 0.0;
            $hasil[$periode][$baris->jenis] = $hasil[$periode][$baris->jenis] + (float) $baris->jumlah;
        }

        return $hasil;
    }

    /** Jumlah masuk/keluar satu bulan (dari catatan kas apa adanya). */
    public static function jumlahBulan(string $periode): array
    {
        [$tahun, $bulan] = array_map('intval', explode('-', $periode));

        return [
            'masuk' => (float) Kas::query()->where('jenis', 'masuk')
                ->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)->sum('jumlah'),
            'keluar' => (float) Kas::query()->where('jenis', 'keluar')
                ->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)->sum('jumlah'),
        ];
    }

    /**
     * Rantai bulan berurutan (bulan kosong ikut) dengan saldo bersambung.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function rantai(): array
    {
        $jumlah = self::jumlahPerBulan();
        $simpanan = KasBulan::query()->with('penutup:id,name')->get()->keyBy('periode');
        $sekarang = Carbon::now()->format('Y-m');

        $semua = array_merge(array_keys($jumlah), $simpanan->keys()->all(), [$sekarang]);
        sort($semua);
        $mulai = $semua[0] ?? $sekarang;

        $rantai = [];
        $saldoJalan = 0.0;
        $kursor = Carbon::createFromFormat('Y-m', $mulai)->startOfMonth();
        $batas = Carbon::now()->startOfMonth();

        while ($kursor->lte($batas)) {
            $periode = $kursor->format('Y-m');
            $snap = $simpanan->get($periode);
            $live = $jumlah[$periode] ?? ['masuk' => 0.0, 'keluar' => 0.0];

            if ($snap && $snap->ditutup) {
                $masuk = (float) $snap->masuk;
                $keluar = (float) $snap->keluar;
                $awal = (float) $snap->saldo_awal;
                $akhir = (float) $snap->saldo_akhir;
            } else {
                $masuk = $live['masuk'];
                $keluar = $live['keluar'];
                $awal = $saldoJalan;
                $akhir = $awal + $masuk - $keluar;
            }

            $rantai[$periode] = [
                'periode' => $periode,
                'label' => $kursor->translatedFormat('F Y'),
                'label_pendek' => $kursor->translatedFormat('M Y'),
                'saldo_awal' => round($awal, 2),
                'masuk' => round($masuk, 2),
                'keluar' => round($keluar, 2),
                'saldo_akhir' => round($akhir, 2),
                'ditutup' => (bool) ($snap->ditutup ?? false),
                'ada_baris' => (bool) $snap,
                'ditutup_at' => $snap?->ditutup_at,
                'penutup' => $snap?->penutup?->name,
                'catatan' => $snap?->catatan,
                'berjalan' => $periode === $sekarang,
                // selisih antara catatan kas sekarang dan angka yang dibekukan saat ditutup
                'selisih' => ($snap && $snap->ditutup)
                    ? round(($live['masuk'] - $masuk) + ($keluar - $live['keluar']), 2)
                    : 0.0,
            ];

            $saldoJalan = $akhir;
            $kursor->addMonth();
        }

        return $rantai;
    }

    /**
     * Rantai untuk ditampilkan: deretan bulan yang ada isinya, sedangkan bulan-bulan
     * kosong yang berurutan digabung jadi satu penanda (supaya tampilan tidak penuh baris Rp 0).
     */
    public static function untukTampilan(): array
    {
        $baris = [];
        $kosongAwal = null;
        $kosongAkhir = null;
        $kosongJumlah = 0;

        foreach (self::rantai() as $b) {
            $penting = $b['masuk'] != 0 || $b['keluar'] != 0 || $b['ditutup'] || $b['ada_baris'] || $b['berjalan'];

            if (! $penting) {
                $kosongAwal ??= $b['label_pendek'];
                $kosongAkhir = $b['label_pendek'];
                $kosongJumlah++;
                continue;
            }

            if ($kosongAwal !== null) {
                $baris[] = [
                    'jenis' => 'kosong',
                    'dari' => $kosongAwal,
                    'sampai' => $kosongAkhir,
                    'jumlah_bulan' => $kosongJumlah,
                ];
                $kosongAwal = null;
                $kosongJumlah = 0;
            }

            $baris[] = ['jenis' => 'bulan'] + $b;
        }

        return $baris;
    }

    /** Saldo kas terkini (ujung rantai). */
    public static function saldoTerkini(): float
    {
        $rantai = self::rantai();
        $terakhir = end($rantai);

        return $terakhir ? (float) $terakhir['saldo_akhir'] : 0.0;
    }

    /** Bulan berjalan (baris rantai terakhir). */
    public static function bulanBerjalan(): array
    {
        $rantai = self::rantai();
        $terakhir = end($rantai);

        return $terakhir ?: [];
    }

    /**
     * TUTUP KAS: bekukan angka bulan ini, lalu buka bulan berikutnya
     * dengan saldo awal = saldo akhir bulan ini.
     *
     * @return array{periode: string, saldo_akhir: float, berikut: string}
     */
    public static function tutup(string $periode, ?int $pengguna = null, ?string $catatan = null): array
    {
        $rantai = self::rantai();
        abort_unless(isset($rantai[$periode]), 404);

        $awal = (float) $rantai[$periode]['saldo_awal'];
        $live = self::jumlahBulan($periode);
        $akhir = round($awal + $live['masuk'] - $live['keluar'], 2);

        $baris = KasBulan::query()->firstOrNew(['periode' => $periode]);
        $baris->fill([
            'saldo_awal' => $awal,
            'masuk' => $live['masuk'],
            'keluar' => $live['keluar'],
            'saldo_akhir' => $akhir,
            'ditutup' => true,
            'ditutup_oleh' => $pengguna ?? $baris->ditutup_oleh,
            'ditutup_at' => Carbon::now(),
            'catatan' => $catatan ?? $baris->catatan,
        ])->save();

        $berikut = Carbon::createFromFormat('Y-m', $periode)->addMonthNoOverflow()->format('Y-m');
        $barisBerikut = KasBulan::query()->firstOrNew(['periode' => $berikut]);
        if (! $barisBerikut->exists) {
            $barisBerikut->saldo_awal = $akhir;
            $barisBerikut->save();
        }

        return ['periode' => $periode, 'saldo_akhir' => $akhir, 'berikut' => $berikut];
    }

    /** HITUNG ULANG: sama seperti tutup, dipakai bila catatan kas bulan itu berubah setelah ditutup. */
    public static function hitungUlang(string $periode, ?int $pengguna = null): array
    {
        return self::tutup($periode, $pengguna);
    }

    /** BUKA KEMBALI bulan yang sudah ditutup (angka kembali dihitung dari catatan kas). */
    public static function buka(string $periode): void
    {
        KasBulan::query()->where('periode', $periode)->delete();

        $berikut = Carbon::createFromFormat('Y-m', $periode)->addMonthNoOverflow()->format('Y-m');
        $barisBerikut = KasBulan::query()->where('periode', $berikut)->first();

        if (! $barisBerikut || $barisBerikut->ditutup) {
            return;
        }

        $rantai = self::rantai();
        $kosong = ($rantai[$berikut]['masuk'] ?? 0) == 0 && ($rantai[$berikut]['keluar'] ?? 0) == 0;

        // bulan berikutnya tadi lahir otomatis dan belum dipakai → cukup dibuang
        if ($kosong) {
            $barisBerikut->delete();

            return;
        }

        $barisBerikut->saldo_awal = (float) ($rantai[$periode]['saldo_akhir'] ?? 0);
        $barisBerikut->save();
    }
}
