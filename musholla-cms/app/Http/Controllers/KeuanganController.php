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

        // rekap 12 bulan terakhir
        $awal = Carbon::now()->startOfMonth()->subMonths(11);
        $rekap = [];
        for ($i = 0; $i < 12; $i++) {
            $kunci = (clone $awal)->addMonths($i)->format('Y-m');
            $rekap[$kunci] = ['masuk' => 0.0, 'keluar' => 0.0];
        }
        $baris = Kas::query()
            ->where('tanggal', '>=', $awal->toDateString())
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bulan, jenis, SUM(jumlah) as total")
            ->groupBy('bulan', 'jenis')
            ->get();
        foreach ($baris as $b) {
            if (isset($rekap[$b->bulan][$b->jenis])) {
                $rekap[$b->bulan][$b->jenis] = (float) $b->total;
            }
        }

        $daftarBulan = Kas::query()
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bulan")
            ->whereNotNull('tanggal')
            ->groupBy('bulan')
            ->orderByDesc('bulan')
            ->pluck('bulan');

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
            'rekap' => $rekap,
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
        'members', 'logout', 'account', 'password-reset', 'privacy-policy',
    ];

    private function menu(): array
    {
        return \App\Models\Page::query()
            ->whereNotNull('terbit_at')
            ->whereNotIn('slug', self::BUKAN_MENU)
            ->orderBy('urutan_menu')
            ->orderBy('id')
            ->get(['judul', 'slug'])
            ->map(fn ($p) => ['judul' => $p->judul, 'slug' => $p->slug])
            ->all();
    }

    private function pengaturan(): array
    {
        return \App\Models\Pengaturan::query()->pluck('nilai', 'kunci')->all();
    }
}
