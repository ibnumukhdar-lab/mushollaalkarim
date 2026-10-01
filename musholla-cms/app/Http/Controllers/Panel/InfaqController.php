<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Infaq;
use App\Models\WakafProgram;
use Illuminate\Http\Request;

/**
 * Tindakan cepat modul Infaq: verifikasi (sekaligus mencatat ke kas) dan tolak.
 *
 * Controller ini TIDAK menulis ke tabel kas sendiri — seluruh aturan pencatatan
 * ada di SATU tempat: Infaq::verifikasi() dan Infaq::tolak() (dipakai juga oleh
 * observer dan panel Filament).
 */
class InfaqController extends Controller
{
    /** Verifikasi infaq → mencatat ke kas lewat jalur satu-pintu di model. */
    public function verifikasi(Request $request, int $id)
    {
        $infaq = Infaq::query()->findOrFail($id);

        $kas = $infaq->verifikasi($request->user()->id);

        if (! $kas) {
            return redirect()->route('panel.daftar', 'infaq')->with(
                'sukses',
                'Infaq '.$infaq->nama_donatur.' sudah tercatat di kas sebelumnya — tidak dicatat dua kali.'
            );
        }

        $pesan = 'Infaq '.$infaq->nama_donatur.' diverifikasi dan dicatat ke kas.';

        $program = WakafProgram::untukTujuan($infaq->tujuan);
        if ($program) {
            $pesan .= ' Kemajuan '.$program->nama.' kini Rp '
                .number_format((float) $program->terkumpul, 0, ',', '.')
                .' ('.$program->persen.'%).';
        }

        return redirect()->route('panel.daftar', 'infaq')->with('sukses', $pesan);
    }

    /** Tolak / batalkan infaq — baris kas tertaut ikut dibuang lewat model. */
    public function tolak(Request $request, int $id)
    {
        $infaq = Infaq::query()->findOrFail($id);

        $hasil = $infaq->tolak($request->user()->id);

        return redirect()->route('panel.daftar', 'infaq')
            ->with($hasil['berhasil'] ? 'sukses' : 'galat', $hasil['pesan']);
    }
}
