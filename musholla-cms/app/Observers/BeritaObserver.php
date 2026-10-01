<?php

namespace App\Observers;

use App\Models\Berita;
use App\Services\PushNotif;
use Illuminate\Support\Facades\Log;

/**
 * Tulisan yang baru TERBIT → kirim notifikasi ke perangkat pembaca
 * (yang memasang situs sebagai PWA dan menekan lonceng).
 *
 * Aman dari dobel: kolom `push_pada` diisi begitu notifikasi dikirim, dan
 * tulisan terjadwal diurus perintah push:jadwal.
 */
class BeritaObserver
{
    public function saved(Berita $berita): void
    {
        if (! $berita->terbit_at || $berita->push_pada) {
            return;
        }

        // Belum waktunya terbit (terjadwal) → dibiarkan, ditangani push:jadwal.
        if ($berita->terbit_at->isFuture()) {
            return;
        }

        try {
            $hasil = PushNotif::tulisanBaru($berita);
            $berita->forceFill(['push_pada' => now()])->saveQuietly();

            Log::info('Notifikasi tulisan baru: ' . $berita->judul . ' → '
                . $hasil['terkirim'] . ' terkirim, ' . $hasil['gagal'] . ' gagal. ' . $hasil['catatan']);
        } catch (\Throwable $e) {
            Log::warning('Notifikasi tulisan gagal disiapkan: ' . $e->getMessage());
        }
    }
}
