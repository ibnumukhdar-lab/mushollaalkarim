<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Support\Panel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Infaq & QRIS: rekening tujuan + gambar QRIS yang dipakai halaman Mari Berinfaq.
 */
class InfaqQrisController extends Controller
{
    public const KUNCI = ['rekening_bank', 'rekening_nomor', 'rekening_nama', 'qris_path', 'infaq_catatan'];

    public function index()
    {
        return view('panel.qris', [
            'grup' => Panel::menuSamping(),
            'nilai' => Pengaturan::query()->whereIn('kunci', self::KUNCI)->pluck('nilai', 'kunci')->all(),
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'rekening_bank' => ['nullable', 'string', 'max:80'],
            'rekening_nomor' => ['nullable', 'string', 'max:40'],
            'rekening_nama' => ['nullable', 'string', 'max:120'],
            'infaq_catatan' => ['nullable', 'string', 'max:600'],
            'qris' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'qris.mimes' => 'Gambar QRIS harus berupa JPG, PNG, atau WEBP.',
            'qris.max' => 'Ukuran gambar QRIS maksimal 5 MB.',
        ]);

        foreach (['rekening_bank', 'rekening_nomor', 'rekening_nama', 'infaq_catatan'] as $kunci) {
            Pengaturan::updateOrCreate(['kunci' => $kunci], ['nilai' => trim((string) ($data[$kunci] ?? ''))]);
        }

        if ($request->hasFile('qris') && $request->file('qris')->isValid()) {
            $lama = Pengaturan::query()->where('kunci', 'qris_path')->value('nilai');
            if ($lama && Storage::disk('public')->exists($lama)) {
                Storage::disk('public')->delete($lama);
            }
            $path = $request->file('qris')->store('qris', 'public');
            Pengaturan::updateOrCreate(['kunci' => 'qris_path'], ['nilai' => $path]);
        }

        return redirect()->route('panel.qris')->with('sukses', 'Data infaq & QRIS tersimpan.');
    }

    /** Hapus gambar QRIS. */
    public function hapusQris()
    {
        $lama = Pengaturan::query()->where('kunci', 'qris_path')->value('nilai');
        if ($lama && Storage::disk('public')->exists($lama)) {
            Storage::disk('public')->delete($lama);
        }
        Pengaturan::updateOrCreate(['kunci' => 'qris_path'], ['nilai' => '']);

        return redirect()->route('panel.qris')->with('sukses', 'Gambar QRIS dihapus.');
    }
}
