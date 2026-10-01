<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Infaq extends Model
{
    protected $table = 'infaq';

    protected $fillable = [
        'nama_donatur', 'no_wa', 'nominal', 'tanggal', 'tujuan',
        'bukti_path', 'status', 'keterangan', 'diverifikasi_oleh', 'kas_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
    ];

    /**
     * Penanda bahwa verifikasi() sedang berjalan pada objek ini.
     *
     * Observer `updated()` menerima OBJEK YANG SAMA saat save() di dalam
     * verifikasi(), jadi tanpa penanda ini jalur satu-pintu akan memanggil
     * dirinya sendiri (pencatatan kas dobel).
     */
    public bool $verifikasiBerjalan = false;

    public function verifikator()
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    /** Baris kas yang tercatat untuk infaq ini. */
    public function kas()
    {
        return $this->belongsTo(Kas::class, 'kas_id');
    }

    /**
     * SATU PINTU pencatatan kas: tandai infaq terverifikasi, buat baris kas
     * (jenis masuk), tautkan kedua arah (`infaq.kas_id` ↔ `kas.infaq_id`),
     * lalu tambah kemajuan program wakaf bila tujuannya sebuah program wakaf.
     *
     * Idempoten: bila `kas_id` sudah terisi, tidak ada kas baru dan balikannya null.
     * Dijalankan juga oleh InfaqObserver::updated() dan oleh tombol panel
     * "Catat ke kas", supaya semua jalur memakai aturan yang sama.
     */
    public function verifikasi(?int $userId = null): ?Kas
    {
        $this->verifikasiBerjalan = true;

        try {
            $this->status = 'terverifikasi';
            $this->diverifikasi_oleh = $userId ?: auth()->id();
            $this->save();

            if ($this->kas_id) {
                return null;    // sudah pernah dicatat — jangan dobel
            }

            $kas = Kas::query()->create([
                'tanggal' => $this->tanggal ?: now()->toDateString(),
                'jenis' => 'masuk',
                'kategori' => 'Infaq'.($this->tujuan ? ' — '.$this->tujuan : ''),
                'jumlah' => $this->nominal,
                'keterangan' => 'Infaq dari '.$this->nama_donatur.($this->keterangan ? ' — '.$this->keterangan : ''),
                'bukti_path' => $this->bukti_path,
                'infaq_id' => $this->id,
                'dicatat_oleh' => $userId ?: auth()->id(),
            ]);

            $this->kas_id = $kas->id;
            $this->save();

            // Kemajuan wakaf hanya naik saat kas benar-benar lahir (sekali per infaq).
            WakafProgram::tambahTerkumpul($this->tujuan, (float) $this->nominal);

            return $kas;
        } finally {
            $this->verifikasiBerjalan = false;
        }
    }

    /**
     * Tolak / batalkan infaq — SATU PINTU pula untuk pembatalan.
     *
     * Bila infaq sebelumnya terverifikasi dan punya baris kas tertaut, baris kas
     * itu DIHAPUS dan `kas_id` dikosongkan; kemajuan wakaf dikembalikan.
     * Bulan kas yang sudah DITUTUP (tabel `kas_bulan`) tidak boleh diubah:
     * pembatalan ditolak dengan pesan jelas, tanpa menyentuh apa pun.
     *
     * @return array{berhasil: bool, pesan: string}
     */
    public function tolak(?int $userId = null): array
    {
        $kas = $this->kas_id ? Kas::query()->find($this->kas_id) : null;

        if ($kas && $this->periodeKasDitutup($kas)) {
            return [
                'berhasil' => false,
                'pesan' => 'Infaq '.$this->nama_donatur.' tidak bisa ditolak: catatan kasnya ('
                    .($kas->tanggal?->translatedFormat('F Y') ?? '-')
                    .') berada di bulan yang sudah ditutup. Buka dulu kas bulan itu di halaman Kas, baru tolak infaq ini.',
            ];
        }

        $pernahTerverifikasi = $this->status === 'terverifikasi';

        $this->status = 'ditolak';
        $this->diverifikasi_oleh = $userId ?: auth()->id();
        $this->kas_id = null;
        $this->save();

        if ($kas) {
            $kas->delete();
        }

        $program = $pernahTerverifikasi
            ? WakafProgram::kurangiTerkumpul($this->tujuan, (float) $this->nominal)
            : null;

        $pesan = 'Infaq '.$this->nama_donatur.' ditandai ditolak.';
        if ($kas) {
            $pesan .= ' Catatan kasnya (Rp '.number_format((float) $kas->jumlah, 0, ',', '.').') sudah dihapus.';
        }
        if ($program) {
            $pesan .= ' Kemajuan '.$program->nama.' dikurangi kembali menjadi Rp '
                .number_format((float) $program->terkumpul, 0, ',', '.').'.';
        }

        return ['berhasil' => true, 'pesan' => $pesan];
    }

    /** Apakah bulan si baris kas sudah dibekukan lewat tutup kas bulanan? */
    private function periodeKasDitutup(Kas $kas): bool
    {
        $periode = $kas->tanggal?->format('Y-m');

        if (! $periode) {
            return false;
        }

        return KasBulan::query()
            ->where('periode', $periode)
            ->where('ditutup', true)
            ->exists();
    }
}
