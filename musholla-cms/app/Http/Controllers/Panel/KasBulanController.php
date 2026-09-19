<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Support\KasBulanan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Tutup kas bulanan: membekukan angka satu bulan, lalu membuka bulan berikutnya
 * dengan saldo awal = saldo akhir bulan sebelumnya.
 */
class KasBulanController extends Controller
{
    public function tutup(Request $request)
    {
        $data = $this->periodeValid($request);

        $hasil = KasBulanan::tutup($data['periode'], auth()->id(), $request->input('catatan'));

        return redirect()->route('panel.daftar', 'kas')->with(
            'sukses',
            'Kas ' . $this->label($data['periode']) . ' ditutup. Saldo sisa Rp '
            . number_format($hasil['saldo_akhir'], 0, ',', '.')
            . ' menjadi saldo awal ' . $this->label($hasil['berikut']) . '.'
        );
    }

    public function hitungUlang(Request $request)
    {
        $data = $this->periodeValid($request);

        $hasil = KasBulanan::hitungUlang($data['periode'], auth()->id());

        return redirect()->route('panel.daftar', 'kas')->with(
            'sukses',
            'Kas ' . $this->label($data['periode']) . ' dihitung ulang dari catatan terbaru — saldo akhir Rp '
            . number_format($hasil['saldo_akhir'], 0, ',', '.') . '.'
        );
    }

    public function buka(Request $request)
    {
        $data = $this->periodeValid($request);

        KasBulanan::buka($data['periode']);

        return redirect()->route('panel.daftar', 'kas')->with(
            'sukses',
            'Kas ' . $this->label($data['periode']) . ' dibuka kembali — angkanya kembali mengikuti catatan kas.'
        );
    }

    private function periodeValid(Request $request): array
    {
        return $request->validate([
            'periode' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [], ['periode' => 'bulan']);
    }

    private function label(string $periode): string
    {
        return Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y');
    }
}
