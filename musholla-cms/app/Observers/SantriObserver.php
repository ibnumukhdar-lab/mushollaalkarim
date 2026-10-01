<?php

namespace App\Observers;

use App\Models\Santri;
use App\Services\WhatsApp;
use Illuminate\Support\Facades\DB;

/**
 * Santri diaktifkan → beri tahu orang tuanya.
 * Nomor diambil dari akun orang tua (bila tertaut) atau dari catatan pendaftaran.
 */
class SantriObserver
{
    public function updated(Santri $santri): void
    {
        if (! $santri->wasChanged('aktif') || ! $santri->aktif) {
            return;
        }

        $nomor = null;
        $namaOrtu = 'Orang tua '.$santri->nama;

        if ($santri->ortu_user_id) {
            $u = DB::table('users')->where('id', $santri->ortu_user_id)->first(['name', 'nama_lengkap', 'no_wa']);
            if ($u && $u->no_wa) {
                $nomor = $u->no_wa;
                $namaOrtu = $u->nama_lengkap ?: $u->name;
            }
        }

        if (! $nomor && $santri->catatan && preg_match('~WA:\s*([0-9+()\s-]{8,})~i', $santri->catatan, $m)) {
            $nomor = $m[1];
        }

        if (! $nomor) {
            return;
        }

        WhatsApp::notifikasi('santri_aktif', [
            'nama' => $namaOrtu,
            'nama_ortu' => $namaOrtu,
            'nama_santri' => $santri->nama,
        ], $nomor, $namaOrtu);
    }
}
