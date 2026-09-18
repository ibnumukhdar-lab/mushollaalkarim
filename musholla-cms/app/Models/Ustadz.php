<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ustadz extends Model
{
    protected $table = 'ustadz';

    protected $fillable = [
        'nama',
        'user_id',
        'bidang',
        'no_wa',
        'catatan',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function santri()
    {
        return $this->hasMany(Santri::class, 'ustadz_id');
    }
}
