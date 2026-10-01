<?php

namespace App\Http\Controllers;

use App\Services\TerbitBerita;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint penerbitan BERITA mushollaalkarim.web.id untuk bot asisten penulis.
 *
 * POST /api/tulis
 *   Header : X-Token: <TULIS_TOKEN dari .env musholla>
 *   Isi    : {"judul":"…","isi":"<p>…</p>","kategori":"Kegiatan","terbit":true,"slug":"…"}
 *   Balasan: {"ok":true,"tautan":"https://mushollaalkarim.web.id/berita/…", …}
 *
 * Aman secara bawaan: hanya menambah/memperbarui (tidak ada penghapusan),
 * wajib token, dibatasi 20 permintaan/menit.
 */
class ApiTulisController extends Controller
{
    public function simpan(Request $request, TerbitBerita $mesin): JsonResponse
    {
        $wajib = (string) config('services.tulis.token');
        $kirim = (string) $request->header('X-Token');

        if ($wajib === '' || ! hash_equals($wajib, $kirim)) {
            return response()->json(['ok' => false, 'galat' => 'Token salah atau belum dipasang.'], 401);
        }

        $data = $request->json()->all() ?: $request->all();

        try {
            $berita = $mesin->simpan($data);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'galat' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'galat' => 'Gagal menyimpan: '.$e->getMessage()], 500);
        }

        return response()->json($mesin->hasil($berita), 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
