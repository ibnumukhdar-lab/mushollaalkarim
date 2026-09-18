<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kajian extends Model
{
    protected $table = 'kajian';

    protected $fillable = [
        'judul',
        'pemateri',
        'tema',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'tempat',
        'keterangan',
        'rutin_mingguan',
        'aktif',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'aktif' => 'boolean',
        'rutin_mingguan' => 'boolean',
    ];
}
