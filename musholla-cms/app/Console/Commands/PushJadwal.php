<?php

namespace App\Console\Commands;

use App\Models\Berita;
use App\Services\PushNotif;
use Illuminate\Console\Command;

/**
 * Kirim notifikasi untuk tulisan yang BARU SAJA terbit dan belum pernah
 * dikirim — terutama tulisan yang dijadwalkan (terbit_at di masa depan).
 * Dijalankan cron tiap 5 menit:  php artisan push:jadwal
 */
class PushJadwal extends Command
{
    protected $signature = 'push:jadwal';

    protected $description = 'Kirim notifikasi PWA untuk tulisan yang baru terbit (mis. terjadwal).';

    public function handle(): int
    {
        if (! PushNotif::aktif()) {
            $this->warn('Kunci VAPID belum diisi — tidak ada yang dikirim.');

            return self::SUCCESS;
        }

        $siap = Berita::query()
            ->whereNotNull('terbit_at')
            ->where('terbit_at', '<=', now())
            ->whereNull('push_pada')
            ->orderBy('terbit_at')
            ->limit(20)
            ->get();

        if ($siap->isEmpty()) {
            $this->line('Tidak ada tulisan baru untuk diberitakan.');

            return self::SUCCESS;
        }

        foreach ($siap as $berita) {
            $hasil = PushNotif::tulisanBaru($berita);
            $berita->forceFill(['push_pada' => now()])->saveQuietly();

            $this->info('«' . $berita->judul . '» → ' . $hasil['terkirim'] . ' terkirim, '
                . $hasil['gagal'] . ' gagal. ' . $hasil['catatan']);
        }

        return self::SUCCESS;
    }
}
