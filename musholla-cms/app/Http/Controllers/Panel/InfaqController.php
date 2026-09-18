<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Infaq;
use App\Models\Kas;
use Illuminate\Http\Request;

/**
 * Tindakan cepat modul Infaq: verifikasi (sekaligus mencatat ke kas) dan tolak.
 */
class InfaqController extends Controller
{
    /** Verifikasi infaq → otomatis membuat catatan kas masuk. */
    public function verifikasi(Request $request, int $id)
    {
        $infaq = Infaq::query()->findOrFail($id);

        $infaq->status = 'terverifikasi';
        $infaq->diverifikasi_oleh = $request->user()->id;
        $infaq->save();

        // Catat ke kas bila belum pernah dicatat (anti dobel).
        $sudahAda = Kas::query()
            ->where('jenis', 'masuk')
            ->where('jumlah', $infaq->nominal)
            ->whereDate('tanggal', $infaq->tanggal)
            ->where('keterangan', 'like', 'Infaq dari ' . $infaq->nama_donatur . '%')
            ->exists();

        if (! $sudahAda) {
            Kas::query()->create([
                'tanggal' => $infaq->tanggal ?? now()->toDateString(),
                'jenis' => 'masuk',
                'kategori' => 'Infaq' . ($infaq->tujuan ? ' — ' . $infaq->tujuan : ''),
                'jumlah' => $infaq->nominal,
                'keterangan' => 'Infaq dari ' . $infaq->nama_donatur . ($infaq->keterangan ? ' — ' . $infaq->keterangan : ''),
                'bukti_path' => $infaq->bukti_path,
                'dicatat_oleh' => $request->user()->id,
            ]);
        }

        return redirect()->route('panel.daftar', 'infaq')
            ->with('sukses', 'Infaq ' . $infaq->nama_donatur . ' diverifikasi dan dicatat ke kas.');
    }

    /** Tolak infaq (tidak dicatat ke kas). */
    public function tolak(Request $request, int $id)
    {
        $infaq = Infaq::query()->findOrFail($id);
        $infaq->status = 'ditolak';
        $infaq->diverifikasi_oleh = $request->user()->id;
        $infaq->save();

        return redirect()->route('panel.daftar', 'infaq')
            ->with('sukses', 'Infaq ' . $infaq->nama_donatur . ' ditandai ditolak.');
    }
}
