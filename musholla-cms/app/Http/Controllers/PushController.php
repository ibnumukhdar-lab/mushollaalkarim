<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\PushNotif;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Langganan notifikasi PWA.
 *   GET  /api/push/vapid   → kunci publik untuk berlangganan dari peramban
 *   POST /api/push/simpan  → simpan/perbarui langganan perangkat
 *   POST /api/push/hapus   → matikan langganan perangkat ini
 *   POST /kelola/push/uji  → (pengurus) kirim notifikasi uji
 */
class PushController extends Controller
{
    public function vapid(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'publicKey' => (string) config('services.push.vapid_public'),
        ]);
    }

    public function simpan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        PushSubscription::query()->updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => 'aes128gcm',
                'device' => mb_substr(trim((string) $request->input('device', '')), 0, 100) ?: null,
            ],
        );

        return response()->json(['ok' => true, 'jumlah' => PushNotif::jumlah()]);
    }

    public function hapus(Request $request): JsonResponse
    {
        $endpoint = trim((string) $request->input('endpoint', ''));
        if ($endpoint !== '') {
            PushSubscription::query()->where('endpoint', $endpoint)->delete();
        }

        return response()->json(['ok' => true, 'jumlah' => PushNotif::jumlah()]);
    }

    /** Uji kirim dari panel (hanya pengurus yang sudah masuk). */
    public function uji()
    {
        $hasil = PushNotif::kirim(
            'Notifikasi Musholla Al Karim aktif ✓',
            'Ini contoh notifikasi. Setiap ada tulisan baru, kabarnya akan muncul seperti ini.',
            url('/berita'),
            null,
            'alkarim-uji',
        );

        $pesan = $hasil['ok']
            ? 'Notifikasi uji: '.$hasil['terkirim'].' terkirim, '.$hasil['gagal'].' gagal.'
                .($hasil['catatan'] !== '' ? ' ('.$hasil['catatan'].')' : '')
            : 'Gagal: '.$hasil['catatan'];

        return redirect()->route('panel.daftar', 'berita')->with(
            $hasil['ok'] && $hasil['terkirim'] > 0 ? 'sukses' : 'galat',
            $pesan,
        );
    }
}
