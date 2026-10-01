<?php

// Uji fungsi: angka laporan harus ikut BULAN YANG DIPILIH, bukan bulan berjalan.
use App\Services\WhatsApp;

$rp = fn ($v) => (string) $v;

echo '== variabelBulan(2026-09) =='.PHP_EOL;
$s = WhatsApp::variabelBulan('2026-09');
foreach (['bulan', 'bulan_tahun', 'pemasukan_bulan', 'pengeluaran_bulan', 'saldo_awal', 'saldo_akhir', 'saldo_terkini', 'saldo_terkini_hari_ini', 'masuk_total', 'status_bulan', 'jumlah_catatan'] as $k) {
    echo str_pad($k, 24).': '.$rp($s[$k] ?? '-').PHP_EOL;
}

echo PHP_EOL.'== variabelBulan(2026-10) =='.PHP_EOL;
$o = WhatsApp::variabelBulan('2026-10');
foreach (['bulan', 'pemasukan_bulan', 'pengeluaran_bulan', 'saldo_akhir', 'saldo_terkini_hari_ini', 'status_bulan'] as $k) {
    echo str_pad($k, 24).': '.$rp($o[$k] ?? '-').PHP_EOL;
}

echo PHP_EOL.'== draf laporan (diisi variabel bulan September) =='.PHP_EOL;
$draf = WhatsApp::drafLaporanKas('2026-09');
echo WhatsApp::isiVariabel($draf, array_merge($s, ['nama_donatur' => 'Bapak Ahmad'])).PHP_EOL;

echo PHP_EOL.'== rincian yang salah tulis terdeteksi =='.PHP_EOL;
print_r(WhatsApp::variabelTidakDikenal('{{bulan}} {{saldo_akhir}} {{saldo_akhhir}} {{bulan_laporan}}'));

echo PHP_EOL.'== penerima(kelompok) =='.PHP_EOL;
foreach (['subscriber', 'donatur', 'admin'] as $g) {
    $p = WhatsApp::penerima($g);
    echo str_pad($g, 12).': '.$p->count().' → '.$p->pluck('nama')->implode(', ').PHP_EOL;
}
