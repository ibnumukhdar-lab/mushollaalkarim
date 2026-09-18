<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Santri extends Model
{
    protected $table = 'santri';

    protected $fillable = [
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'kelompok',
        'kelas_sekolah',
        'ortu_user_id',
        'ustadz_id',
        'catatan',
        'foto_path',
        'aktif',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'aktif' => 'boolean',
    ];

    public function ortu()
    {
        return $this->belongsTo(User::class, 'ortu_user_id');
    }

    public function ustadz()
    {
        return $this->belongsTo(Ustadz::class, 'ustadz_id');
    }

    public function penilaian()
    {
        return $this->hasMany(Penilaian::class, 'santri_id');
    }
}
