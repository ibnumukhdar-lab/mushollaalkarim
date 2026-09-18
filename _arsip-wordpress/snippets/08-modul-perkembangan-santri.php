<?php
// SNIPPET WordPress #8: MODUL PERKEMBANGAN SANTRI
// scope: global | status: AKTIF | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

// ====================================================================
// 8. PEMBUATAN TABEL DATABASE KUSTOM UNTUK RIWAYAT NILAI
// ====================================================================
function simta_create_db_table_penilaian() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'simta_perkembangan';
    $charset_collate = $wpdb->get_charset_collate();

    // Kolom 'catatan_ustadz' ditambahkan ke dalam skema tabel
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        santri_id bigint(20) NOT NULL,
        kategori varchar(50) NOT NULL,
        detail_materi text NOT NULL,
        nilai_1 tinyint(1) NOT NULL,
        nilai_2 tinyint(1) NOT NULL,
        nilai_3 tinyint(1) NOT NULL,
        catatan_ustadz text,
        tanggal datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql); // dbDelta otomatis memperbarui tabel jika ada kolom baru
}
add_action('admin_init', 'simta_create_db_table_penilaian');


// ====================================================================
// 9. REGISTRASI MENU SIDEBAR KHUSUS PERKEMBANGAN SANTRI
// ====================================================================
function simta_perkembangan_menu() {
    add_menu_page(
        'Perkembangan Santri', // Page Title
        'Perkembangan',        // Menu Title
        'edit_posts',          // Capability
        'simta-perkembangan',  // Menu Slug
        'simta_perkembangan_page_html', // Callback Function
        'dashicons-chart-line', // Icon
        26                     // Posisi di bawah menu Santri
    );
}
add_action('admin_menu', 'simta_perkembangan_menu');


// ====================================================================
// 10. HALAMAN UTAMA: FORMULIR INPUT & TABEL RIWAYAT
// ====================================================================
function simta_perkembangan_page_html() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'simta_perkembangan';

    // A. PROSES HAPUS DATA
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        check_admin_referer('delete_nilai_' . $_GET['id']);
        $wpdb->delete($table_name, array('id' => intval($_GET['id'])));
        echo '<div class="notice notice-success is-dismissible"><p>Data riwayat berhasil dihapus.</p></div>';
    }

    // B. PROSES SIMPAN/UPDATE DATA FORMULIR
    if (isset($_POST['simta_submit_nilai']) && check_admin_referer('simta_save_nilai_action', 'simta_nilai_nonce')) {
        $santri_id = intval($_POST['santri_id']);
        $kategori  = sanitize_text_field($_POST['kategori']);
        
        $detail_materi = '';
        if ($kategori == 'Tilawah Al-Qur\'an') {
            $detail_materi = 'Juz ' . sanitize_text_field($_POST['tilawah_juz']) . ' - Hal ' . sanitize_text_field($_POST['tilawah_halaman']);
        } elseif ($kategori == 'Latihan Iqro/Tilawati') {
            $detail_materi = 'Jilid ' . sanitize_text_field($_POST['iqro_jilid']) . ' - Hal ' . sanitize_text_field($_POST['iqro_halaman']);
        } elseif ($kategori == 'Hafalan Al-Qur\'an') {
            $detail_materi = 'Juz ' . sanitize_text_field($_POST['hafalan_juz']) . ' | QS. ' . sanitize_text_field($_POST['hafalan_surah']) . ' | Ayat: ' . sanitize_text_field($_POST['hafalan_ayat']);
        }

        $data = array(
            'santri_id'      => $santri_id,
            'kategori'       => $kategori,
            'detail_materi'  => $detail_materi,
            'nilai_1'        => intval($_POST['nilai_1']),
            'nilai_2'        => intval($_POST['nilai_2']),
            'nilai_3'        => intval($_POST['nilai_3']),
            'catatan_ustadz' => sanitize_textarea_field($_POST['catatan_ustadz']),
            'tanggal'        => current_time('mysql')
        );

        if (!empty($_POST['edit_id'])) {
            $wpdb->update($table_name, $data, array('id' => intval($_POST['edit_id'])));
            echo '<div class="notice notice-success is-dismissible"><p>Data riwayat berhasil diperbarui.</p></div>';
        } else {
            $wpdb->insert($table_name, $data);
            echo '<div class="notice notice-success is-dismissible"><p>Data penilaian berhasil disimpan.</p></div>';
        }
    }

    // C. PERSIAPAN DATA UNTUK FORM EDIT
    $edit_data = null;
    // Variabel penampung untuk memecah detail_materi saat edit
    $edit_tilawah_juz = '';
    $edit_tilawah_halaman = '';
    $edit_iqro_jilid = '';
    $edit_iqro_halaman = '';
    $edit_hafalan_juz = '';
    $edit_hafalan_surah = '';
    $edit_hafalan_ayat = '';

    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
        $edit_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['id'])));
        
        // Memecah string detail_materi agar otomatis mengisi input form spesifik
        if ($edit_data) {
            if ($edit_data->kategori == 'Tilawah Al-Qur\'an') {
                // Format: "Juz X - Hal Y" (Jika data lama 'Tilawah Umum', abaikan)
                if ($edit_data->detail_materi != 'Tilawah Umum') {
                    preg_match('/Juz (.*?) - Hal (.*?)$/', $edit_data->detail_materi, $matches);
                    if (count($matches) == 3) {
                        $edit_tilawah_juz = $matches[1];
                        $edit_tilawah_halaman = $matches[2];
                    }
                }
            } elseif ($edit_data->kategori == 'Latihan Iqro/Tilawati') {
                // Format: "Jilid X - Hal Y"
                preg_match('/Jilid (.*?) - Hal (.*?)$/', $edit_data->detail_materi, $matches);
                if (count($matches) == 3) {
                    $edit_iqro_jilid = $matches[1];
                    $edit_iqro_halaman = $matches[2];
                }
            } elseif ($edit_data->kategori == 'Hafalan Al-Qur\'an') {
                // Format: "Juz X | QS. Y | Ayat: Z"
                $parts = explode(' | ', $edit_data->detail_materi);
                if (count($parts) == 3) {
                    $edit_hafalan_juz = str_replace('Juz ', '', $parts[0]);
                    $edit_hafalan_surah = str_replace('QS. ', '', $parts[1]);
                    $edit_hafalan_ayat = str_replace('Ayat: ', '', $parts[2]);
                }
            }
        }
    }

    $santri_list = get_posts(array('post_type' => 'santri', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC'));
    
    // Helper function untuk dropdown nilai edit
    function get_opsi_nilai($selected_val = '') {
        $options = '';
        $labels = [
            1 => '1 - Sangat Kurang (Fatal/Banyak Salah)',
            2 => '2 - Kurang (Perlu Banyak Bimbingan)',
            3 => '3 - Cukup (Standar Dasar Terpenuhi)',
            4 => '4 - Baik (Lancar & Sedikit Kesalahan)',
            5 => '5 - Sangat Baik (Sempurna & Sesuai Kaidah)'
        ];
        foreach ($labels as $val => $label) {
            $selected = ($selected_val == $val) ? 'selected="selected"' : '';
            $options .= "<option value=\"$val\" $selected>$label</option>";
        }
        return $options;
    }

    // D. TAMPILAN UI ADMIN
    ?>
    <style>
        .simta-wrap { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; }
        .simta-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); margin-bottom: 30px; }
        .simta-card h3 { margin-top: 0; padding-bottom: 12px; border-bottom: 1px solid #eee; color: #1d2327; font-size: 1.2em; }
        .simta-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; line-height: 1; }
        .badge-blue { background: #e5f5fa; color: #0073aa; border: 1px solid #00a0d2; }
        .badge-green { background: #e1faea; color: #00a32a; border: 1px solid #46b450; }
        .badge-orange { background: #fdf2e3; color: #d67f05; border: 1px solid #f56e28; }
        .badge-purple { background: #f4f0fa; color: #8a2be2; border: 1px solid #8a2be2; }
        
        .simta-score-container { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .simta-score-item { text-align: center; background: #f6f7f7; padding: 5px 10px; border-radius: 6px; border: 1px solid #e2e4e7; }
        .simta-score { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; background: #1d2327; color: #fff; border-radius: 4px; font-size: 13px; font-weight: bold; }
        .simta-score-label { font-size: 11px; color: #646970; display: block; margin-bottom: 4px; font-weight: 600;}
        
        .simta-predikat { display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 12px; color: #fff; text-align: center; width: 100%; box-sizing: border-box;}
        
        .simta-input-modern { border: 1px solid #8c8f94; border-radius: 4px; padding: 6px 12px; min-height: 32px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.03); }
        .simta-input-modern:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
        .simta-table-head { background: #f6f7f7; }
        .simta-note-box { background: #fdfcf8; border-left: 3px solid #f5cf87; padding: 10px; font-style: italic; font-size: 13px; color: #50575e; }
    </style>

    <div class="wrap simta-wrap">
        <h1 class="wp-heading-inline" style="margin-bottom: 15px;">Perkembangan Santri</h1>
        <hr class="wp-header-end">

        <!-- KOTAK FORMULIR INPUT -->
        <div class="simta-card">
            <h3><span class="dashicons dashicons-edit"></span> <?php echo $edit_data ? 'Edit Data Penilaian' : 'Input Nilai & Catatan Ustadz'; ?></h3>
            <form method="post" action="">
                <?php wp_nonce_field('simta_save_nilai_action', 'simta_nilai_nonce'); ?>
                <?php if ($edit_data): ?>
                    <input type="hidden" name="edit_id" value="<?php echo intval($edit_data->id); ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th style="width: 20%;"><label>Nama Santri</label></th>
                        <td>
                            <select name="santri_id" class="simta-input-modern" required style="width:100%; max-width:400px;">
                                <option value="">-- Cari / Pilih Santri --</option>
                                <?php foreach ($santri_list as $s): ?>
                                    <option value="<?php echo $s->ID; ?>" <?php selected($edit_data ? $edit_data->santri_id : '', $s->ID); ?>><?php echo esc_html($s->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Materi Pelajaran</label></th>
                        <td>
                            <select name="kategori" id="simta_kategori" class="simta-input-modern" required style="width:100%; max-width:400px;">
                                <option value="">-- Pilih Materi --</option>
                                <option value="Tilawah Al-Qur'an" <?php selected($edit_data ? $edit_data->kategori : '', "Tilawah Al-Qur'an"); ?>>Tilawah Al-Qur'an</option>
                                <option value="Latihan Iqro/Tilawati" <?php selected($edit_data ? $edit_data->kategori : '', "Latihan Iqro/Tilawati"); ?>>Latihan Iqro/Tilawati</option>
                                <option value="Hafalan Al-Qur'an" <?php selected($edit_data ? $edit_data->kategori : '', "Hafalan Al-Qur'an"); ?>>Hafalan Al-Qur'an</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <!-- BUNGKUSAN DINAMIS TILAWAH -->
                <div id="wrap_tilawah" style="display:none; background:#f4f0fa; padding:15px; border-radius:6px; border-left:4px solid #8a2be2; margin-top:15px;">
                    <strong style="display:block; margin-bottom:10px; color:#8a2be2;">Target Tilawah:</strong>
                    <select name="tilawah_juz" class="simta-input-modern" style="margin-right:10px; min-width: 120px;">
                        <option value="">Pilih Juz</option>
                        <?php for($i=1; $i<=30; $i++) {
                            echo "<option value='$i' " . selected($edit_tilawah_juz, $i, false) . ">Juz $i</option>";
                        } ?>
                    </select>
                    <input type="text" name="tilawah_halaman" class="simta-input-modern" placeholder="Halaman (Cth: 15-16)" style="width: 200px;" value="<?php echo esc_attr($edit_tilawah_halaman); ?>">
                </div>

                <!-- BUNGKUSAN DINAMIS IQRO -->
                <div id="wrap_iqro" style="display:none; background:#f0f6fc; padding:15px; border-radius:6px; border-left:4px solid #2271b1; margin-top:15px;">
                    <strong style="display:block; margin-bottom:10px; color:#2271b1;">Target Latihan:</strong>
                    <select name="iqro_jilid" class="simta-input-modern" style="margin-right:10px; min-width: 120px;">
                        <option value="">Pilih Jilid</option>
                        <?php for($i=1; $i<=6; $i++) {
                            echo "<option value='$i' " . selected($edit_iqro_jilid, $i, false) . ">Jilid $i</option>";
                        } ?>
                    </select>
                    <input type="number" name="iqro_halaman" class="simta-input-modern" placeholder="Halaman (Contoh: 15)" style="width: 200px;" value="<?php echo esc_attr($edit_iqro_halaman); ?>">
                </div>

                <!-- BUNGKUSAN DINAMIS HAFALAN -->
                <div id="wrap_hafalan" style="display:none; background:#f3f9f4; padding:15px; border-radius:6px; border-left:4px solid #46b450; margin-top:15px;">
                    <strong style="display:block; margin-bottom:10px; color:#46b450;">Target Hafalan:</strong>
                    <select name="hafalan_juz" class="simta-input-modern" style="margin-right:10px; min-width: 120px;">
                        <option value="">Pilih Juz</option>
                        <?php for($i=1; $i<=30; $i++) {
                            echo "<option value='$i' " . selected($edit_hafalan_juz, $i, false) . ">Juz $i</option>";
                        } ?>
                    </select>
                    <select name="hafalan_surah" class="simta-input-modern" style="margin-right:10px; width: 220px;">
                        <option value="">Pilih Surah</option>
                        <?php 
                        $surahs = ['Al-Fatihah','Al-Baqarah','Ali \'Imran','An-Nisa\'','Al-Ma\'idah','Al-An\'am','Al-A\'raf','Al-Anfal','At-Taubah','Yunus','Hud','Yusuf','Ar-Ra\'d','Ibrahim','Al-Hijr','An-Nahl','Al-Isra\'','Al-Kahf','Maryam','Ta-Ha','Al-Anbiya\'','Al-Hajj','Al-Mu\'minun','An-Nur','Al-Furqan','Asy-Syu\'ara\'','An-Naml','Al-Qasas','Al-\'Ankabut','Ar-Rum','Luqman','As-Sajdah','Al-Ahzab','Saba\'','Fatir','Ya-Sin','As-Saffat','Sad','Az-Zumar','Gafir','Fussilat','Asy-Syura','Az-Zukhruf','Ad-Dukhan','Al-Jasiyah','Al-Ahqaf','Muhammad','Al-Fath','Al-Hujurat','Qaf','Az-Zariyat','At-Tur','An-Najm','Al-Qamar','Ar-Rahman','Al-Waqi\'ah','Al-Hadid','Al-Mujadilah','Al-Hasyr','Al-Mumtahanah','As-Saff','Al-Jumu\'ah','Al-Munafiqun','At-Tagabun','At-Talaq','At-Tahrim','Al-Mulk','Al-Qalam','Al-Haqqah','Al-Ma\'arij','Nuh','Al-Jinn','Al-Muzzammil','Al-Muddassir','Al-Qiyamah','Al-Insan','Al-Mursalat','An-Naba\'','An-Nazi\'at','\'Abasa','At-Takwir','Al-Infitar','Al-Mutaffifin','Al-Insyiqaq','Al-Buruj','At-Tariq','Al-A\'la','Al-Gasyiyah','Al-Fajr','Al-Balad','Asy-Syams','Al-Lail','Ad-Duha','Asy-Syarh','At-Tin','Al-\'Alaq','Al-Qadr','Al-Bayyinah','Az-Zalzalah','Al-\'Adiyat','Al-Qari\'ah','At-Takasur','Al-\'Asr','Al-Humazah','Al-Fil','Quraisy','Al-Ma\'un','Al-Kausar','Al-Kafirun','An-Nasr','Al-Lahab','Al-Ikhlas','Al-Falaq','An-Nas'];
                        foreach($surahs as $idx => $s) {
                            echo "<option value='$s' " . selected($edit_hafalan_surah, $s, false) . ">".($idx+1).". $s</option>";
                        }
                        ?>
                    </select>
                    <input type="text" name="hafalan_ayat" class="simta-input-modern" placeholder="Ayat (Cth: 1-15)" style="width: 180px;" value="<?php echo esc_attr($edit_hafalan_ayat); ?>">
                </div>

                <!-- BUNGKUSAN NILAI -->
                <div id="wrap_nilai" style="display:none; margin-top:20px;">
                    <table class="form-table">
                        <tr>
                            <th style="width: 20%;"><label id="label_nilai_1">Kriteria 1</label></th>
                            <td><select name="nilai_1" class="simta-input-modern" required style="width:100%; max-width:400px;">
                                <option value="">-- Tetapkan Skor --</option>
                                <?php echo get_opsi_nilai($edit_data ? $edit_data->nilai_1 : ''); ?>
                            </select></td>
                        </tr>
                        <tr>
                            <th><label id="label_nilai_2">Kriteria 2</label></th>
                            <td><select name="nilai_2" class="simta-input-modern" required style="width:100%; max-width:400px;">
                                <option value="">-- Tetapkan Skor --</option>
                                <?php echo get_opsi_nilai($edit_data ? $edit_data->nilai_2 : ''); ?>
                            </select></td>
                        </tr>
                        <tr>
                            <th><label>Kelancaran Umum</label></th>
                            <td><select name="nilai_3" class="simta-input-modern" required style="width:100%; max-width:400px;">
                                <option value="">-- Tetapkan Skor --</option>
                                <?php echo get_opsi_nilai($edit_data ? $edit_data->nilai_3 : ''); ?>
                            </select></td>
                        </tr>
                        <tr>
                            <th><label>Catatan Ustadz</label></th>
                            <td>
                                <textarea name="catatan_ustadz" rows="4" class="simta-input-modern" style="width:100%; max-width:400px;" placeholder="Tuliskan evaluasi, pujian, atau bagian yang perlu diperbaiki santri..."><?php echo $edit_data ? esc_textarea($edit_data->catatan_ustadz) : ''; ?></textarea>
                                <p class="description">Catatan ini akan tampil di riwayat dan dapat dibaca oleh orang tua.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit" style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #eee;">
                    <button type="submit" name="simta_submit_nilai" class="button button-primary button-large"><span class="dashicons dashicons-saved"></span> <?php echo $edit_data ? 'Update Data' : 'Simpan Data'; ?></button>
                    <?php if ($edit_data): ?>
                        <a href="?page=simta-perkembangan" class="button button-secondary button-large" style="margin-left: 10px;">Batal Edit / Tambah Baru</a>
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <!-- TABEL RIWAYAT -->
        <div class="simta-card" style="padding: 0; overflow: hidden;">
            <h3 style="padding: 20px 20px 15px; margin: 0; background: #fafafa;"><span class="dashicons dashicons-list-view"></span> Tabel Riwayat Perkembangan</h3>
            <table class="wp-list-table widefat fixed striped table-view-list" style="border: 0; border-top: 1px solid #ccd0d4; margin: 0;">
                <thead class="simta-table-head">
                    <tr>
                        <th style="width:12%; padding-left: 20px;">Tanggal</th>
                        <th style="width:16%;">Nama Santri</th>
                        <th style="width:16%;">Materi & Detail</th>
                        <th style="width:20%;">Catatan Ustadz</th>
                        <th style="width:18%;">Skor Evaluasi</th>
                        <th style="width:10%;">Predikat</th>
                        <th style="width:8%; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $riwayat = $wpdb->get_results("SELECT * FROM $table_name ORDER BY tanggal DESC LIMIT 100");
                    if ($riwayat) {
                        foreach ($riwayat as $row) {
                            $nama_santri = get_the_title($row->santri_id);
                            $del_url = wp_nonce_url("?page=simta-perkembangan&action=delete&id={$row->id}", 'delete_nilai_'.$row->id);
                            $edit_url = "?page=simta-perkembangan&action=edit&id={$row->id}";
                            
                            // Tentukan warna badge berdasarkan kategori
                            $badge_class = 'badge-blue';
                            if ($row->kategori == "Hafalan Al-Qur'an") $badge_class = 'badge-green';
                            if ($row->kategori == "Latihan Iqro/Tilawati") $badge_class = 'badge-orange';
                            if ($row->kategori == "Tilawah Al-Qur'an") $badge_class = 'badge-purple';

                            // Format Label Kriteria berdasarkan kategori
                            $lbl1 = "Makhraj"; $lbl2 = "Tajwid"; $lbl3 = "Lancar";
                            if ($row->kategori == "Latihan Iqro/Tilawati") {
                                $lbl1 = "Tepat"; $lbl2 = "Makhraj"; 
                            }

                            // Kalkulasi Predikat Berdasarkan Rata-rata Nilai
                            $rata_rata = ($row->nilai_1 + $row->nilai_2 + $row->nilai_3) / 3;
                            $predikat = '';
                            $bg_predikat = '';

                            if ($rata_rata == 5) {
                                $predikat = 'Mumtaz';
                                $bg_predikat = '#28a745'; // Hijau
                            } elseif ($rata_rata >= 4) {
                                $predikat = 'Jayyid Jiddan';
                                $bg_predikat = '#17a2b8'; // Biru Cyan
                            } elseif ($rata_rata >= 3) {
                                $predikat = 'Jayyid';
                                $bg_predikat = '#0073aa'; // Biru WP
                            } elseif ($rata_rata >= 2) {
                                $predikat = 'Maqbul';
                                $bg_predikat = '#fd7e14'; // Orange
                            } else {
                                $predikat = 'Mardud';
                                $bg_predikat = '#d63638'; // Merah
                            }

                            echo "<tr>";
                            echo "<td style='padding-left: 20px;'><span class='dashicons dashicons-calendar-alt' style='color:#8c8f94; font-size:16px;'></span> " . date('d M Y', strtotime($row->tanggal)) . "<br><small style='color:#8c8f94;'>" . date('H:i', strtotime($row->tanggal)) . "</small></td>";
                            echo "<td><strong>{$nama_santri}</strong></td>";
                            echo "<td>
                                    <strong>{$row->kategori}</strong><br>
                                    <span class='simta-badge {$badge_class}' style='margin-top: 5px;'>{$row->detail_materi}</span>
                                  </td>";
                            echo "<td>";
                            if (!empty($row->catatan_ustadz)) {
                                echo "<div class='simta-note-box'>" . nl2br(esc_html($row->catatan_ustadz)) . "</div>";
                            } else {
                                echo "<span style='color:#a7aaad; font-style:italic;'>- Tidak ada catatan -</span>";
                            }
                            echo "</td>";
                            
                            // Skor Berjejer ke samping (Flexbox)
                            echo "<td>
                                    <div class='simta-score-container'>
                                        <div class='simta-score-item'><span class='simta-score-label'>{$lbl1}</span><span class='simta-score'>{$row->nilai_1}</span></div>
                                        <div class='simta-score-item'><span class='simta-score-label'>{$lbl2}</span><span class='simta-score'>{$row->nilai_2}</span></div>
                                        <div class='simta-score-item'><span class='simta-score-label'>{$lbl3}</span><span class='simta-score'>{$row->nilai_3}</span></div>
                                    </div>
                                  </td>";
                            
                            // Kolom Predikat
                            echo "<td><span class='simta-predikat' style='background-color: {$bg_predikat};'>{$predikat}</span></td>";

                            echo "<td style='text-align: center;'>
                                    <a href='{$edit_url}' class='button button-small' style='margin-bottom:4px; width:100%;'><span class='dashicons dashicons-edit'></span> Edit</a><br>
                                    <a href='{$del_url}' class='button button-small' style='color:#d63638; border-color:#d63638; width:100%;' onclick='return confirm(\"Hapus riwayat ini secara permanen?\")'><span class='dashicons dashicons-trash'></span> Hapus</a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; padding: 30px; color: #8c8f94;'>Belum ada data perkembangan yang diinput.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JAVASCRIPT UNTUK LOGIKA DINAMIS MENU DROPDOWN -->
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var elKategori = document.getElementById('simta_kategori');
        var wrapTilawah= document.getElementById('wrap_tilawah');
        var wrapIqro   = document.getElementById('wrap_iqro');
        var wrapHafal  = document.getElementById('wrap_hafalan');
        var wrapNilai  = document.getElementById('wrap_nilai');
        
        var lblSatu = document.getElementById('label_nilai_1');
        var lblDua  = document.getElementById('label_nilai_2');

        function updateFormDinamis() {
            var val = elKategori.value;
            wrapTilawah.style.display = 'none';
            wrapIqro.style.display  = 'none';
            wrapHafal.style.display = 'none';
            wrapNilai.style.display = 'none';

            if(val) {
                wrapNilai.style.display = 'block';
            }

            if (val === "Tilawah Al-Qur'an") {
                wrapTilawah.style.display = 'block';
                lblSatu.innerText = "Makhraj (Kesesuaian Huruf)";
                lblDua.innerText  = "Tajwid (Hukum Bacaan)";
                document.querySelector('[name="tilawah_juz"]').required = true;
                document.querySelector('[name="tilawah_halaman"]').required = true;
                document.querySelector('[name="iqro_jilid"]').required = false;
                document.querySelector('[name="iqro_halaman"]').required = false;
                document.querySelector('[name="hafalan_juz"]').required = false;
                document.querySelector('[name="hafalan_surah"]').required = false;
                document.querySelector('[name="hafalan_ayat"]').required = false;
            } else if (val === "Latihan Iqro/Tilawati") {
                wrapIqro.style.display = 'block';
                lblSatu.innerText = "Ketepatan (Huruf yang dibaca)";
                lblDua.innerText  = "Makhraj (Pengucapan)";
                document.querySelector('[name="iqro_jilid"]').required = true;
                document.querySelector('[name="iqro_halaman"]').required = true;
                document.querySelector('[name="tilawah_juz"]').required = false;
                document.querySelector('[name="tilawah_halaman"]').required = false;
                document.querySelector('[name="hafalan_juz"]').required = false;
                document.querySelector('[name="hafalan_surah"]').required = false;
                document.querySelector('[name="hafalan_ayat"]').required = false;
            } else if (val === "Hafalan Al-Qur'an") {
                wrapHafal.style.display = 'block';
                lblSatu.innerText = "Makhraj (Pengucapan)";
                lblDua.innerText  = "Tajwid (Hukum Bacaan)";
                document.querySelector('[name="hafalan_juz"]').required = true;
                document.querySelector('[name="hafalan_surah"]').required = true;
                document.querySelector('[name="hafalan_ayat"]').required = true;
                document.querySelector('[name="tilawah_juz"]').required = false;
                document.querySelector('[name="tilawah_halaman"]').required = false;
                document.querySelector('[name="iqro_jilid"]').required = false;
                document.querySelector('[name="iqro_halaman"]').required = false;
            }
        }

        function hapusRequiredDinamis() {
            document.querySelector('[name="tilawah_juz"]').required = false;
            document.querySelector('[name="tilawah_halaman"]').required = false;
            document.querySelector('[name="iqro_jilid"]').required = false;
            document.querySelector('[name="iqro_halaman"]').required = false;
            document.querySelector('[name="hafalan_juz"]').required = false;
            document.querySelector('[name="hafalan_surah"]').required = false;
            document.querySelector('[name="hafalan_ayat"]').required = false;
        }

        elKategori.addEventListener('change', updateFormDinamis);
        
        if(elKategori.value !== "") {
            updateFormDinamis();
        }
    });
    </script>
    <?php
}