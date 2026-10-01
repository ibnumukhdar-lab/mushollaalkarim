<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Kampanye pengiriman WhatsApp (satu kali "kirim ke banyak penerima"). */
class WaBroadcast extends Model
{
    protected $table = 'wa_broadcast';

    protected $fillable = [
        'judul', 'isi', 'grup', 'mode', 'wa_template_id',
        'pesan_terkirim', 'jumlah_target', 'terkirim', 'gagal',
        'mulai_at', 'selesai_at', 'status', 'oleh_user_id',
    ];

    protected $casts = [
        'mulai_at' => 'datetime',
        'selesai_at' => 'datetime',
    ];

    public function pesan()
    {
        return $this->hasMany(WaPesan::class, 'wa_broadcast_id');
    }

    public function template()
    {
        return $this->belongsTo(WaTemplate::class, 'wa_template_id');
    }

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'oleh_user_id');
    }

    public function getKemajuanAttribute(): array
    {
        $total = max($this->pesan()->count(), 1);

        return [
            'total' => $this->pesan()->count(),
            'terkirim' => $this->pesan()->where('status', 'terkirim')->count(),
            'menunggu' => $this->pesan()->where('status', 'menunggu')->count(),
            'gagal' => $this->pesan()->where('status', 'gagal')->count(),
            'persen' => (int) round($this->pesan()->where('status', 'terkirim')->count() / $total * 100),
        ];
    }
}
