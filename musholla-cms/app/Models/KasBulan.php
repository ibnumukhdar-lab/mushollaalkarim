<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Rekap kas satu bulan (dasar tutup kas bulanan). */
class KasBulan extends Model
{
    protected $table = 'kas_bulan';

    protected $fillable = [
        'periode',
        'saldo_awal',
        'masuk',
        'keluar',
        'saldo_akhir',
        'ditutup',
        'ditutup_oleh',
        'ditutup_at',
        'catatan',
    ];

    protected $casts = [
        'saldo_awal' => 'decimal:2',
        'masuk' => 'decimal:2',
        'keluar' => 'decimal:2',
        'saldo_akhir' => 'decimal:2',
        'ditutup' => 'boolean',
        'ditutup_at' => 'datetime',
    ];

    public function penutup()
    {
        return $this->belongsTo(User::class, 'ditutup_oleh');
    }
}
