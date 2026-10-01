<?php

// Data uji lokal: meniru keadaan produksi (September ditutup, Oktober sudah jalan)
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

DB::table('users')->where('email', 'uji@local.test')->update([
    'password' => Hash::make('rahasia123'), 'peran' => 'admin', 'aktif' => true,
    'nama_lengkap' => 'Uji Pengurus', 'name' => 'Uji Pengurus', 'no_wa' => '081200000001',
    'updated_at' => now(),
]);

DB::table('kas')->delete();
DB::table('kas_bulan')->delete();
DB::table('donatur')->delete();

// September: masuk 900.000, keluar 683.745 → saldo akhir 216.255 (sama seperti produksi)
DB::table('kas')->insert([
    ['tanggal' => '2026-09-05', 'jenis' => 'masuk', 'jumlah' => 500000, 'keterangan' => 'Infaq Jumat', 'created_at' => now(), 'updated_at' => now()],
    ['tanggal' => '2026-09-20', 'jenis' => 'masuk', 'jumlah' => 400000, 'keterangan' => 'Donasi wakaf', 'created_at' => now(), 'updated_at' => now()],
    ['tanggal' => '2026-09-10', 'jenis' => 'keluar', 'jumlah' => 500000, 'keterangan' => 'Honor pemateri', 'created_at' => now(), 'updated_at' => now()],
    ['tanggal' => '2026-09-28', 'jenis' => 'keluar', 'jumlah' => 183745, 'keterangan' => 'Listrik & air', 'created_at' => now(), 'updated_at' => now()],
    // Oktober SUDAH ada pergerakan → laporan September wajib tetap 216.255
    ['tanggal' => '2026-10-01', 'jenis' => 'masuk', 'jumlah' => 50000, 'keterangan' => 'Infaq 1 Oktober', 'created_at' => now(), 'updated_at' => now()],
    ['tanggal' => '2026-10-01', 'jenis' => 'keluar', 'jumlah' => 10000, 'keterangan' => 'Beli galon', 'created_at' => now(), 'updated_at' => now()],
]);

DB::table('kas_bulan')->insert([
    'periode' => '2026-09', 'saldo_awal' => 0, 'masuk' => 900000, 'keluar' => 683745,
    'saldo_akhir' => 216255, 'ditutup' => true, 'ditutup_oleh' => 1, 'ditutup_at' => '2026-10-01 08:00:00',
    'catatan' => 'Ditutup pengurus', 'created_at' => now(), 'updated_at' => now(),
]);

DB::table('donatur')->insert([
    ['nama' => 'Bapak Ahmad', 'no_wa' => '081200000011', 'aktif' => true, 'kategori' => 'Jamaah', 'created_at' => now(), 'updated_at' => now()],
    ['nama' => 'Ibu Siti', 'no_wa' => '081200000012', 'aktif' => true, 'kategori' => 'Jamaah', 'created_at' => now(), 'updated_at' => now()],
]);

echo 'SEED_OK '.DB::table('kas')->count().' kas, '.DB::table('donatur')->count().' donatur'.PHP_EOL;
