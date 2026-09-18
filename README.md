# Musholla Al Karim — migrasi WordPress ➜ Laravel

Situs **https://mushollaalkarim.web.id** ("Al Karim Islamic Center — Pusat Pertumbuhan dan Kemajuan Ummat Berbasis Musholla") sekarang berjalan di **WordPress 7.1.1 + Elementor**. Repo ini adalah tempat kerja migrasinya ke **Laravel**, dengan aturan main: **situs lama tidak boleh rusak** sampai versi Laravel siap, dan semua konten/otak aplikasi harus terbawa utuh.

## Aturan main migrasi (kesepakatan)
1. WordPress tetap hidup apa adanya sampai Laravel siap dipindah (cutover dilakukan dengan menukar folder docroot, bukan menghapus WP).
2. Semua konten & logika diselamatkan lebih dulu (backup + ekspor), baru dibangun ulang.
3. Tampilan/template boleh dibuat ulang (lebih lega, warna tenang/navy), tetapi **isi dan alur aplikasi tidak boleh berubah**.
4. URL lama dipertahankan (atau dialihkan 301) supaya tautan, bookmark, dan hasil Google tidak hilang.

## Peta situs & aplikasi (hasil telaah 18 Sep 2026)

### Halaman publik (20 halaman publish, mayoritas Elementor)
- `/` → **Home** (ID 44, Elementor)
- `/mari-berinfaq` — donasi/infaq
- `/laporan-kas` — laporan keuangan
- `/majelis-tadris-al-quran` (MTA), `/mta-lil-awlaad`
- `/berita` (CPT `berita_musholla`, 3 entri) + program wakaf (CPT `program_wakaf`, 2 entri)
- Halaman lomba: `/lomba-muadzin`, `/lomba-story-telling`, `/data-peserta`, `/juri-muadzin`
- `/pendaftaran-santri`, `/sukses-daftar`

### Halaman ber-login (Ultimate Member + shortcode custom)
- `/login`, `/register`, `/logout`, `/members`, `/account`, `/user`, `/password-reset`
- `/dashboard-ortu` (orang tua santri), `/dashboard-ustadz` (pengajar)

### Peran pengguna saat ini (7 akun)
`administrator`, `pengurus_musholla`, `ustadz`, `um_ustadz`, `um_ortu`

### Data (WordPress)
- CPT: `berita_musholla` (3), `program_wakaf` (2), `ustadz` (2), `santri` (3), `pendaftar_adzan` (2)
- Tabel khusus: `wpvr_simta_perkembangan` (6 baris), `wpvr_wa_donatur` (6), `wpvr_wa_templates` (1)
- Media: 11 lampiran (uploads 117 berkas, 12 MB)

### Otak aplikasi = plugin **Code Snippets** (23 snippet, ±366 KB PHP)
Seluruh logika ada di tabel `wpvr_snippets`, bukan di tema. **Salinan lengkap ada di `_arsip-wordpress/snippets/`.**
Snippet yang AKTIF (yang harus dipindah ke Laravel):
| # | Nama | Ukuran | Isi |
|---|------|--------|-----|
| 05 | Coding Musholla | 53 KB | gaya/tampilan + blok dasar situs |
| 06 | UPDATE JADWAL KAJIAN | 18 KB | jadwal kajian |
| 07 | SIMTA (Majelis Tadris Al-Qur'an) | 28 KB | inti program MTA + portal juri |
| 08 | MODUL PERKEMBANGAN SANTRI | 30 KB | pencatatan perkembangan santri |
| 10 | Dashboard Ustadz | 18,5 KB | panel pengajar |
| 11 | Dashboard Ortu | 12 KB | panel orang tua |
| 12 | Metabox relasi santri ↔ orang tua | 2 KB | penghubung akun ortu dengan santri |
| 13 | Sapaan Ortu | 0,8 KB | salam dinamis di dashboard |
| 14 | SIMTA Form Pendaftaran | 10 KB | pendaftaran santri baru |
| 18 | WA Sender Donatur (Style 2) | 30 KB | kirim WhatsApp ke donatur |
| 19 | Pendaftar Lomba Azan | 17 KB | pendaftaran lomba adzan |
| 23 | Penilaian Lomba Azan V.2 | 39 KB | penilaian juri (versi terbaru) |
Nonaktif (arsip, tidak perlu dipindah): #1–4, #9, #15–17, #20–22.

Integrasi eksternal yang terdeteksi: tautan **WhatsApp** (wa.me) dan satu tautan **Google Drive** (halaman sukses daftar).

## Backup (18 Sep 2026)
- Server (di luar docroot): `~/backup-mushollaalkarim/20260918/`
  `db-u8151173_wp495.sql.gz` (31 tabel), `uploads.tar.gz` (117 berkas), `kode-wp.tar.gz` (87 MB), `wp-config.php.simpan`, `snippets/`
- Salinan di PC: `D:\backup-mushollaalkarim\20260918\` (dump + uploads + wp-config)

## Rencana (urutan kerja)
1. ✅ Inventaris + backup penuh + ekspor 23 snippet
2. ⏳ Ekspor konten (halaman/tulisan/CPT/meta/media) ke JSON sebagai bahan impor
3. ⏳ Mockup statis desain baru → disetujui Fahri dulu sebelum dikoding
4. ⏳ Kerangka Laravel (Blade + Alpine + Tailwind; panel admin ringan) di `main`
5. ⏳ Import konten + pindahkan modul per modul (SIMTA, perkembangan santri, dashboard, lomba, WA donatur)
6. ⏳ Uji lokal (situs + form + peran) → deploy dengan penukaran docroot + peta pengalihan 301 → verifikasi

## Struktur repo
- `_arsip-wordpress/` — kode WordPress yang penting (snippet = otak aplikasi, .htaccess, functions tema). Referensi, bukan untuk dijalankan.
- (nanti) aplikasi Laravel di root repo.
