<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaBroadcast extends Model
{
    protected $table = 'wa_broadcast';

    protected $fillable = [
        'wa_template_id',
        'pesan_terkirim',
        'jumlah_target',
        'terkirim',
        'gagal',
        'mulai_at',
        'selesai_at',
        'status',
        'oleh_user_id',
    ];

    protected $casts = [
        'mulai_at' => 'datetime',
        'selesai_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(WaTemplate::class, 'wa_template_id');
    }
}
