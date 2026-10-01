<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kas extends Model
{
    protected $table = 'kas';

    protected $fillable = [
        'tanggal',
        'jenis',
        'kategori',
        'jumlah',
        'keterangan',
        'bukti_path',
        'dicatat_oleh',
        'infaq_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
    ];

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    /** Infaq asal baris kas ini (bila dicatat dari verifikasi infaq). */
    public function infaq()
    {
        return $this->belongsTo(Infaq::class, 'infaq_id');
    }
}
