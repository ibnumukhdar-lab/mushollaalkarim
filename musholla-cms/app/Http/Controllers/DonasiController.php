<?php

namespace App\Http\Controllers;

use App\Models\Infaq;
use App\Models\Page;
use App\Models\Pengaturan;
use App\Models\WakafProgram;
use Illuminate\Http\Request;

/**
 * Mari Berinfaq — form dalam aplikasi (dulu memakai form plugin WordPress).
 * Kiriman masuk ke tabel `infaq` dengan status "menunggu", lalu diperiksa pengurus.
 */
class DonasiController extends Controller
{
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
        return Pengaturan::query()->pluck('nilai', 'kunci')->all();
    }

    public function tampilkan(Request $request)
    {
        $awalBulan = \Illuminate\Support\Carbon::now()->startOfMonth();

        return view('publik.berinfaq', [
            'menu' => $this->menu(),
            'pengaturan' => $this->pengaturan(),
            'wakaf' => WakafProgram::query()->where('aktif', true)->orderBy('urutan')->get(),
            // Program bawaan musholla (selain program wakaf yang dikelola di panel)
            'makanGratis' => (float) Infaq::query()->where('status', 'terverifikasi')
                ->where('tujuan', 'like', '%makan%')->sum('nominal'),
            'operasionalBulanIni' => (float) \App\Models\Kas::query()
                ->where('jenis', 'masuk')
                ->whereBetween('tanggal', [$awalBulan->toDateString(), \Illuminate\Support\Carbon::now()->toDateString()])
                ->where(function ($q) {
                    $q->where('kategori', 'like', '%operasional%')
                        ->orWhere('keterangan', 'like', '%operasional%');
                })
                ->sum('jumlah'),
            'terkirim' => session('infaq_terkirim'),
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'nama_donatur' => ['required', 'string', 'max:120'],
            'no_wa' => ['required', 'string', 'max:25'],
            'nominal' => ['required', 'numeric', 'min:1000'],
            'tujuan' => ['nullable', 'string', 'max:160'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'bukti' => ['nullable', 'image', 'max:2048'],
        ], [], [
            'nama_donatur' => 'nama',
            'no_wa' => 'nomor WhatsApp',
            'nominal' => 'nominal',
        ]);

        $bukti = $request->hasFile('bukti')
            ? $request->file('bukti')->store('bukti-infaq', 'public')
            : null;

        Infaq::query()->create([
            'nama_donatur' => $data['nama_donatur'],
            'no_wa' => $data['no_wa'],
            'nominal' => $data['nominal'],
            'tanggal' => now()->toDateString(),
            'tujuan' => $data['tujuan'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
            'bukti_path' => $bukti,
            'status' => 'menunggu',
        ]);

        return redirect('/mari-berinfaq')->with('infaq_terkirim', true);
    }
}
