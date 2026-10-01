<?php

// Data uji/benih: program "Infaq Operasional" + rincian kebutuhan (draf untuk Fahri).
// Nominal: 4 pos pertama mengikuti catatan kas NYATA; sisanya pos rutin (draf, bisa diubah di panel).
use App\Models\WakafProgram;

$program = WakafProgram::query()->updateOrCreate(
    ['slug' => 'infaq-operasional'],
    [
        'nama' => 'Infaq Operasional',
        'jenis' => 'infaq',
        'periode_label' => 'Kebutuhan setiap bulan',
        'kata_kunci_kas' => 'operasional',
        'target' => 2200000,
        'aktif' => true,
        'urutan' => 3,
        'keterangan' => "Musholla Al Karim terbuka setiap hari untuk sholat berjamaah, kajian, dan kegiatan jamaah. "
            ."Supaya semua itu berjalan tanpa mengganggu kenyamanan beribadah, ada biaya rutin yang harus ditutup "
            ."setiap bulan: listrik, air, kebersihan, pemeliharaan bangunan, sampai langganan WhatsApp broadcast yang "
            ."dipakai mengabari Bapak/Ibu setiap bulan.",
    ]
);

$rincian = [
    ['Tagihan listrik musholla', 1, 'bulan', 100000, 'Rata-rata Rp 87.000–110.000 per bulan'],
    ['Tagihan air PDAM', 1, 'bulan', 70000, null],
    ['Langganan WhatsApp Broadcast (laporan & kabar ke donatur)', 1, 'bulan', 101500, 'Nota September 2026: Rp 101.500'],
    ['Pemeliharaan & pengembangan sistem informasi website musholla', 1, 'bulan', 300000, 'Nota September 2026: Rp 300.000'],
    ['Honor pemateri kajian rutin', 4, 'kali', 125000, 'Kajian pekanan musholla'],
    ['Kebersihan & perlengkapan (sabun, pewangi, tisu, air minum)', 1, 'bulan', 150000, null],
    ['Konsumsi kegiatan & jamaah (kopi, teh, gula, air mineral)', 1, 'bulan', 150000, null],
    ['Pemeliharaan bangunan & alat (lampu, kipas, kabel, cat)', 1, 'bulan', 250000, null],
    ['Administrasi bank & token listrik darurat', 1, 'bulan', 78500, null],
    ['Cadangan perbaikan mendadak', 1, 'bulan', 500000, 'Mis. keran bocor, kipas rusak, hujan bocor di teras'],
];

$program->rincian()->delete();
foreach ($rincian as $i => $r) {
    $program->rincian()->create([
        'nama' => $r[0], 'jumlah' => $r[1], 'satuan' => $r[2],
        'harga_satuan' => $r[3], 'catatan' => $r[4], 'urutan' => $i + 1,
    ]);
}

echo 'BENIH_OK program '.$program->nama.' (slug '.$program->slug.') → '.$program->rincian()->count().' baris, total Rp '
    .number_format($program->fresh()->total_rincian, 0, ',', '.').PHP_EOL;
