<?php

namespace App\Http\Controllers;

use App\Models\Kas;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Laporan keuangan musholla — SEPENUHNYA dari basis data aplikasi.
 * Tidak ada lagi tarikan ke Google Sheet / layanan luar.
 */
class KeuanganController extends Controller
{
    public function publik(Request $request)
    {
        $bulan = $request->query('bulan');
        $jenis = $request->query('jenis');
        $kategori = $request->query('kategori');
        $cari = trim((string) $request->query('cari', ''));

        $saring = Kas::query();
        if ($bulan && preg_match('/^\d{4}-\d{2}$/', (string) $bulan)) {
            [$tahun, $bln] = explode('-', (string) $bulan);
            $saring->whereYear('tanggal', (int) $tahun)->whereMonth('tanggal', (int) $bln);
        }
        if (in_array($jenis, ['masuk', 'keluar'], true)) {
            $saring->where('jenis', $jenis);
        }
        if ($kategori) {
            $saring->where('kategori', $kategori);
        }
        if ($cari !== '') {
            $saring->where(fn ($q) => $q->where('keterangan', 'like', "%{$cari}%")->orWhere('kategori', 'like', "%{$cari}%"));
        }

        $masukSaring = (float) (clone $saring)->where('jenis', 'masuk')->sum('jumlah');
        $keluarSaring = (float) (clone $saring)->where('jenis', 'keluar')->sum('jumlah');

        $transaksi = (clone $saring)
            ->with('pencatat:id,name')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // saldo sebenarnya: seluruh catatan, bukan hanya yang tersaring
        $masukSemua = (float) Kas::where('jenis', 'masuk')->sum('jumlah');
        $keluarSemua = (float) Kas::where('jenis', 'keluar')->sum('jumlah');

        // rekap bulanan dengan saldo bersambung (satu sumber: App\Support\KasBulanan)
        $rekapBulanan = \App\Support\KasBulanan::untukTampilan();
        $saldoTerkini = \App\Support\KasBulanan::saldoTerkini();

        $daftarBulan = Kas::query()
            ->whereNotNull('tanggal')
            ->orderByDesc('tanggal')
            ->pluck('tanggal')
            ->map(fn ($t) => \Illuminate\Support\Carbon::parse($t)->format('Y-m'))
            ->unique()
            ->values();

        $daftarKategori = Kas::query()
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori');

        return view('publik.laporan-kas', [
            'menu' => $this->menu(),
            'pengaturan' => $this->pengaturan(),
            'transaksi' => $transaksi,
            'masukSaring' => $masukSaring,
            'keluarSaring' => $keluarSaring,
            'masukSemua' => $masukSemua,
            'keluarSemua' => $keluarSemua,
            'jumlahMasuk' => Kas::query()->where('jenis', 'masuk')->count(),
            'jumlahKeluar' => Kas::query()->where('jenis', 'keluar')->count(),
            'catatanTerakhir' => Kas::query()->max('tanggal'),
            'saldoTerkini' => $saldoTerkini,
            'rekapBulanan' => $rekapBulanan,
            'daftarBulan' => $daftarBulan,
            'daftarKategori' => $daftarKategori,
            'bulan' => $bulan,
            'jenis' => $jenis,
            'kategori' => $kategori,
            'cari' => $cari,
        ]);
    }

    /** Halaman yang tidak ditampilkan di menu publik. */
    private const BUKAN_MENU = [
        'home-page', 'home', 'laporan-kas', 'laporan-keuangan', 'user', 'login', 'register',
        'members', 'masuk', 'daftar', 'anggota', 'logout', 'keluar', 'account', 'password-reset', 'privacy-policy',
    ];

    private function menu(): array
    {
        return \App\Support\Menu::utama();
    }

    private function pengaturan(): array
    {
        return \App\Models\Pengaturan::query()->pluck('nilai', 'kunci')->all();
    }
}
