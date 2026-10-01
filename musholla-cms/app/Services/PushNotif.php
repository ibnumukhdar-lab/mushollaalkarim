<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Pengirim notifikasi web (PWA) untuk Musholla Al Karim.
 *
 * Kunci VAPID disimpan di .env (VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY) dan dibaca
 * lewat config('services.push'). Notifikasi sampai ke perangkat yang sudah
 * memasang situs sebagai PWA dan menekan tombol lonceng (memberi izin).
 */
class PushNotif
{
    /** Apakah kunci VAPID sudah diisi? */
    public static function aktif(): bool
    {
        return self::kunci() !== null;
    }

    /** Jumlah perangkat yang berlangganan. */
    public static function jumlah(): int
    {
        return PushSubscription::query()->count();
    }

    /** @return array{0:string,1:string}|null [publik, pribadi] */
    private static function kunci(): ?array
    {
        $pub = trim((string) config('services.push.vapid_public'));
        $priv = trim((string) config('services.push.vapid_private'));

        return ($pub !== '' && $priv !== '') ? [$pub, $priv] : null;
    }

    /**
     * Kirim satu notifikasi ke semua perangkat yang berlangganan.
     *
     * @return array{ok:bool,terkirim:int,gagal:int,catatan:string}
     */
    public static function kirim(string $judul, string $isi, string $tautan = '/', ?string $gambar = null, string $tag = 'alkarim'): array
    {
        $kunci = self::kunci();
        if (! $kunci) {
            return ['ok' => false, 'terkirim' => 0, 'gagal' => 0, 'catatan' => 'Kunci VAPID belum diisi.'];
        }

        $langganan = PushSubscription::query()->get();
        if ($langganan->isEmpty()) {
            return ['ok' => true, 'terkirim' => 0, 'gagal' => 0, 'catatan' => 'Belum ada perangkat yang berlangganan.'];
        }

        $webPush = new WebPush(['VAPID' => [
            'subject' => (string) config('services.push.subject'),
            'publicKey' => $kunci[0],
            'privateKey' => $kunci[1],
        ]]);

        $muatan = json_encode(array_filter([
            'title' => $judul,
            'body' => $isi,
            'tag' => $tag,
            'icon' => '/icons/icon-192.png',
            'badge' => '/icons/icon-192.png',
            'image' => $gambar,
            'data' => ['url' => $tautan],
            'vibrate' => [200, 90, 200],
        ], fn ($nilai) => $nilai !== null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $rusak = 0;
        foreach ($langganan as $s) {
            // Langganan lama/rusak (kunci tidak sah) dilewati & dibersihkan —
            // jangan sampai satu data buruk menggagalkan seluruh pengiriman.
            if (! self::kunciSah($s->public_key, 65) || ! self::kunciSah($s->auth_token, 16)) {
                PushSubscription::query()->where('id', $s->id)->delete();
                $rusak++;
                continue;
            }

            $webPush->queueNotification(Subscription::create([
                'endpoint' => $s->endpoint,
                'publicKey' => $s->public_key,
                'authToken' => $s->auth_token,
                'contentEncoding' => $s->content_encoding ?: 'aes128gcm',
            ]), $muatan);
        }

        $terkirim = 0;
        $gagal = 0;
        $sebab = [];

        if ($rusak > 0) {
            $sebab[] = $rusak.' langganan rusak dibersihkan';
        }

        try {
            $laporanSemua = iterator_to_array($webPush->flush());
        } catch (\Throwable $e) {
            return ['ok' => false, 'terkirim' => 0, 'gagal' => $langganan->count(), 'catatan' => 'Pengiriman gagal: '.mb_substr($e->getMessage(), 0, 120)];
        }

        foreach ($laporanSemua as $laporan) {
            if ($laporan->isSuccess()) {
                $terkirim++;
                continue;
            }

            $gagal++;
            $alasan = trim((string) ($laporan->getReason() ?? ''));
            $endpoint = (string) ($laporan->getEndpoint() ?? '');

            // Perangkat sudah tidak berlangganan (404/410) → bersihkan agar tidak dicoba terus.
            if ($endpoint !== '' && preg_match('~404|410|expired|unsubscribed|tidak ditemukan~i', $alasan)) {
                PushSubscription::query()->where('endpoint', $endpoint)->delete();
            }

            if ($alasan !== '') {
                $sebab[] = mb_substr($alasan, 0, 110);
            }
        }

        return ['ok' => true, 'terkirim' => $terkirim, 'gagal' => $gagal, 'catatan' => implode(' | ', array_slice($sebab, 0, 4))];
    }

    /** Apakah kunci langganan (base64 url-safe) sah dan panjangnya sesuai? */
    private static function kunciSah(?string $nilai, int $panjangBiner): bool
    {
        $nilai = trim((string) $nilai);
        if ($nilai === '') {
            return false;
        }

        $padat = strtr($nilai, '-_', '+/');
        $padat .= str_repeat('=', (4 - strlen($padat) % 4) % 4);
        $biner = base64_decode($padat, true);

        if ($biner === false || strlen($biner) !== $panjangBiner) {
            return false;
        }

        // Kunci publik P-256 selalu diawali bita 0x04 (tidak terkompresi).
        return $panjangBiner !== 65 || $biner[0] === "\x04";
    }

    /** Notifikasi untuk tulisan yang baru terbit. */
    public static function tulisanBaru($berita): array
    {
        $tautan = url('/berita/' . $berita->slug);
        $ringkas = \App\Support\Tulis::ringkas($berita->ringkasan ?: $berita->isi, 150);

        $gambar = (string) ($berita->gambar_sampul ?? '');
        if ($gambar !== '' && ! str_starts_with($gambar, 'http')) {
            $gambar = url($gambar);
        }

        return self::kirim(
            (string) $berita->judul,
            $ringkas !== '' ? $ringkas : 'Ada tulisan baru di Musholla Al Karim.',
            $tautan,
            $gambar !== '' ? $gambar : null,
            'alkarim-tulisan-' . $berita->id,
        );
    }
}
