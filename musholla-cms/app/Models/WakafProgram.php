<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WakafProgram extends Model
{
    protected $table = 'wakaf_program';

    protected $fillable = [
        'slug',
        'nama',
        'jenis',
        'keterangan',
        'periode_label',
        'kata_kunci_kas',
        'jumlah',
        'satuan',
        'harga_satuan',
        'target',
        'terkumpul',
        'gambar_path',
        'urutan',
        'aktif',
    ];

    protected $casts = [
        'target' => 'decimal:2',
        'terkumpul' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'jumlah' => 'integer',
        'aktif' => 'boolean',
    ];

    /**
     * Bila target dana dikosongkan tetapi jumlah & harga satuan diisi,
     * target dihitung sendiri (jumlah × harga satuan) — pengurus tidak
     * perlu mengalikan manual dan tidak ada angka yang bertentangan.
     *
     * Kolom `slug` (alamat halaman /wakaf/<slug>) juga diisi otomatis dari
     * nama program bila dikosongkan — dari jalur panel mana pun.
     */
    protected static function booted(): void
    {
        static::saving(function (self $wakaf) {
            $jumlah = (int) ($wakaf->jumlah ?? 0);
            $harga = (float) ($wakaf->harga_satuan ?? 0);

            if (blank($wakaf->target) && $jumlah > 0 && $harga > 0) {
                $wakaf->target = $jumlah * $harga;
            }

            if (blank($wakaf->slug) && filled($wakaf->nama)) {
                $wakaf->slug = self::slugUnik((string) $wakaf->nama, $wakaf->id);
            }
        });
    }

    /** Slug unik dari nama program (ditambah -2, -3, … bila sudah dipakai). */
    public static function slugUnik(string $nama, ?int $kecuali = null): string
    {
        $dasar = Str::slug($nama) ?: 'program-wakaf';
        $calon = $dasar;
        $n = 2;

        while (self::query()->where('slug', $calon)
            ->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali))
            ->exists()) {
            $calon = $dasar . '-' . $n++;
        }

        return $calon;
    }

    /**
     * Alamat halaman pembaca program ini (/wakaf/<slug> atau /infaq/<slug>).
     * Bila slug belum ada (data lama), kembalikan alamat berinfaq supaya
     * tautan di beranda tidak pernah menuju halaman kosong.
     */
    public function getTautanAttribute(): string
    {
        if (blank($this->slug)) {
            return url('/mari-berinfaq');
        }

        return url('/'.($this->jenis_infaq ? 'infaq' : 'wakaf').'/'.$this->slug);
    }

    /** Program jenis "infaq" (ajakan dana/operasional) atau "wakaf" (barang). */
    public function getJenisInfaqAttribute(): bool
    {
        return ($this->attributes['jenis'] ?? 'wakaf') === 'infaq';
    }

    /** Awalan alamat halaman: /infaq untuk program infaq, /wakaf untuk wakaf. */
    public function getAwalanAlamatAttribute(): string
    {
        return $this->jenis_infaq ? 'infaq' : 'wakaf';
    }

    /** Baris rincian kebutuhan program (urut). */
    public function rincian(): HasMany
    {
        return $this->hasMany(ProgramRincian::class, 'wakaf_program_id')
            ->orderBy('urutan')->orderBy('id');
    }

    /** Total rincian kebutuhan (0 bila belum ada rincian). */
    public function getTotalRincianAttribute(): float
    {
        $baris = $this->relationLoaded('rincian') ? $this->rincian : $this->rincian()->get();

        return round((float) $baris->sum(fn ($b) => $b->subtotal), 2);
    }

    /**
     * Angka kebutuhan yang dipakai di situs: total rincian bila rincian diisi,
     * kalau tidak kolom target. Jadi total pada tabel selalu = jumlah barisnya.
     */
    public function getKebutuhanAttribute(): float
    {
        return $this->total_rincian > 0 ? $this->total_rincian : (float) ($this->attributes['target'] ?? 0);
    }

    /**
     * Dana terkumpul yang ditampilkan: bila `kata_kunci_kas` diisi (mis. "operasional"),
     * dipakai catatan KAS bulan berjalan yang cocok — sama seperti kartu di Mari Berinfaq.
     * Bila kosong, dipakai kolom terkumpul seperti program wakaf.
     */
    public function getProgresAttribute(): float
    {
        $kunci = trim((string) ($this->attributes['kata_kunci_kas'] ?? ''));

        if ($kunci === '') {
            return (float) ($this->attributes['terkumpul'] ?? 0);
        }

        $awal = \Illuminate\Support\Carbon::now()->startOfMonth();

        return (float) \App\Models\Kas::query()
            ->where('jenis', 'masuk')
            ->whereBetween('tanggal', [$awal->toDateString(), \Illuminate\Support\Carbon::now()->toDateString()])
            ->where(function ($q) use ($kunci) {
                $q->where('kategori', 'like', '%'.$kunci.'%')
                    ->orWhere('keterangan', 'like', '%'.$kunci.'%');
            })
            ->sum('jumlah');
    }

    /** Kemajuan pengumpulan terhadap kebutuhan program (0–100). */
    public function getPersenKebutuhanAttribute(): int
    {
        $butuh = $this->kebutuhan;

        return $butuh > 0 ? (int) min(100, round($this->progres / $butuh * 100)) : 0;
    }

    /**
     * Alamat gambar untuk halaman pembaca. Mengembalikan null bila berkasnya
     * tidak ada, supaya tampilan memakai penanda kosong — bukan gambar rusak.
     */
    public function getGambarUrlAttribute(): ?string
    {
        $isi = $this->attributes['gambar_path'] ?? null;

        if (blank($isi)) {
            return null;
        }

        if (Str::startsWith($isi, ['http://', 'https://'])) {
            return $isi;
        }

        $isi = ltrim($isi, '/');

        if (! preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $isi)) {
            return null;
        }

        return Storage::disk('public')->exists($isi) ? url('/berkas/'.$isi) : null;
    }

    /** "3 unit × Rp450.000" — baris keterangan jumlah barang (null bila tak diisi). */
    public function getRingkasBarangAttribute(): ?string
    {
        $jumlah = (int) ($this->attributes['jumlah'] ?? 0);
        $harga = (float) ($this->attributes['harga_satuan'] ?? 0);
        $satuan = trim((string) ($this->attributes['satuan'] ?? ''));

        if ($jumlah < 1 && $harga <= 0) {
            return null;
        }

        $bagian = [];
        if ($jumlah > 0) {
            $bagian[] = $jumlah.($satuan !== '' ? ' '.$satuan : ' unit');
        }
        if ($harga > 0) {
            $bagian[] = 'Rp '.number_format($harga, 0, ',', '.');
        }

        return implode(' × ', $bagian);
    }

    /** Program wakaf yang cocok dengan tujuan infaq (nama program). */
    public static function untukTujuan(?string $tujuan): ?self
    {
        $nama = trim((string) $tujuan);
        if ($nama === '') {
            return null;
        }

        return self::query()->where('aktif', true)
            ->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])
            ->first();
    }

    /**
     * Tambah dana terkumpul saat infaq ber-tujuan wakaf DIVERSIFIKASI.
     * Dipanggil saat transisi status → terverifikasi (sekali saja per infaq),
     * jadi angka tidak bisa dobel.
     */
    public static function tambahTerkumpul(?string $tujuan, float $nominal): ?self
    {
        $program = self::untukTujuan($tujuan);
        if (! $program || $nominal <= 0) {
            return null;
        }

        $program->terkumpul = (float) $program->terkumpul + $nominal;
        $program->save();

        return $program;
    }

    /** Kurangi kembali bila verifikasi dibatalkan / ditolak. */
    public static function kurangiTerkumpul(?string $tujuan, float $nominal): ?self
    {
        $program = self::untukTujuan($tujuan);
        if (! $program || $nominal <= 0) {
            return null;
        }

        $program->terkumpul = max(0, (float) $program->terkumpul - $nominal);
        $program->save();

        return $program;
    }

    /** Kemajuan pengumpulan dana (0–100). */
    public function getPersenAttribute(): int
    {
        $target = (float) ($this->attributes['target'] ?? 0);
        if ($target <= 0) {
            return 0;
        }

        return (int) min(100, round(((float) ($this->attributes['terkumpul'] ?? 0) / $target) * 100));
    }
}
