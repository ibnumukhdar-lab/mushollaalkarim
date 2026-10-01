<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu baris antrean WhatsApp untuk satu penerima. */
class WaPesan extends Model
{
    protected $table = 'wa_pesan';

    protected $fillable = [
        'wa_broadcast_id', 'nama', 'nomor', 'pesan', 'status', 'via', 'dikirim_at', 'galat',
    ];

    protected $casts = [
        'dikirim_at' => 'datetime',
    ];

    public function kampanye()
    {
        return $this->belongsTo(WaBroadcast::class, 'wa_broadcast_id');
    }

    /** Tautan WhatsApp siap tekan untuk mode manual. */
    public function getTautanAttribute(): string
    {
        return \App\Services\WhatsApp::tautan($this->nomor, (string) $this->pesan);
    }

    public function getStatusLencanaAttribute(): array
    {
        return match ($this->status) {
            'terkirim' => ['Terkirim', 'hijau'],
            'gagal' => ['Gagal', 'merah'],
            default => ['Menunggu', 'kuning'],
        };
    }
}
