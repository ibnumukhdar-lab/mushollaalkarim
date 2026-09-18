<?php
// SNIPPET WordPress #18: WA Sender Donatur (Style 2)
// scope: global | status: AKTIF | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

// Mencegah akses langsung ke file
if ( ! defined( 'ABSPATH' ) ) exit;

// =========================================================
// 1. BUAT & UPDATE TABEL DATABASE (Donatur & Template)
// =========================================================
add_action( 'admin_init', 'wa_donatur_create_db_table' );

function wa_donatur_create_db_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // Tabel 1: Data Kontak & Donatur
    $table_name = $wpdb->prefix . 'wa_donatur';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nama varchar(255) NOT NULL,
        nomor_wa varchar(50) NOT NULL,
        kategori varchar(255) DEFAULT 'donatur' NOT NULL,
        is_subscribed tinyint(1) DEFAULT 1 NOT NULL,
        tanggal_dibuat datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql );

    // Paksa update struktur tabel jika versi sebelumnya belum punya kolom 'kategori'
    $col_check = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = 'kategori'");
    if(empty($col_check)){
        $wpdb->query("ALTER TABLE $table_name ADD kategori varchar(255) DEFAULT 'donatur' NOT NULL");
    }

    // Tabel 2: Bank Template Pesan
    $table_template = $wpdb->prefix . 'wa_templates';
    $sql2 = "CREATE TABLE $table_template (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        judul varchar(255) NOT NULL,
        isi_template text NOT NULL,
        tanggal_dibuat datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql2 );
}

// =========================================================
// 2. REGISTRASI MENU DASHBOARD
// =========================================================
add_action( 'admin_menu', 'wa_donatur_register_menu' );

function wa_donatur_register_menu() {
    add_menu_page('Modul WhatsApp', 'WA Donatur', 'manage_options', 'wa-modul-main', 'wa_donatur_main_callback', 'dashicons-whatsapp', 25);
    add_submenu_page('wa-modul-main', 'Dashboard WA', 'Dashboard', 'manage_options', 'wa-modul-main', 'wa_donatur_main_callback');
    add_submenu_page('wa-modul-main', 'Data Kontak', 'Data Kontak', 'manage_options', 'wa-modul-donatur', 'wa_donatur_data_callback');
    add_submenu_page('wa-modul-main', 'Broadcast Pesan', 'Broadcast & Antrean', 'manage_options', 'wa-modul-riwayat', 'wa_donatur_riwayat_callback');
    add_submenu_page('wa-modul-main', 'Template Pesan', 'Template Teks', 'manage_options', 'wa-modul-template', 'wa_donatur_template_callback');
    add_submenu_page('wa-modul-main', 'Setting Integrasi API', 'Setting Integrasi', 'manage_options', 'wa-modul-setting', 'wa_donatur_setting_callback');
}

// =========================================================
// 3. FUNGSI HELPER
// =========================================================
function wa_format_nomor_hp($nomor) {
    $nomor = preg_replace('/[^0-9]/', '', $nomor); 
    if (substr($nomor, 0, 1) == '0') { $nomor = '62' . substr($nomor, 1); }
    return $nomor;
}

// =========================================================
// 4. CALLBACK HALAMAN: DASHBOARD UTAMA
// =========================================================
function wa_donatur_main_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wa_donatur';
    $table_template = $wpdb->prefix . 'wa_templates';

    $total_donatur = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
    $aktif = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE is_subscribed = 1");
    $total_template = $wpdb->get_var("SELECT COUNT(id) FROM $table_template");
    
    $api_status = (get_option('wa_dripsender_webhook') && get_option('wa_dripsender_token')) 
        ? '<span style="color:#10b981; font-weight:bold;">✔ Terhubung</span>' : '<span style="color:#ef4444; font-weight:bold;">✖ Belum Dikonfigurasi</span>';

    echo '<div class="wrap"><h1>Dashboard Modul WhatsApp</h1>';
    echo '<div style="display:flex; gap:20px; margin-top:20px; flex-wrap:wrap;">';
    
    echo '<div style="flex:1; min-width:250px; background:#fff; padding:20px; border:1px solid #e2e8f0; border-radius:8px;">';
    echo '<h3>👥 Database Kontak</h3>';
    echo '<h1 style="font-size:30px; margin:0;">' . intval($total_donatur) . ' <span style="font-size:14px; font-weight:normal;">Kontak</span></h1>';
    echo '<p style="color:#10b981;">' . intval($aktif) . ' Kontak Aktif Siap Broadcast</p>';
    echo '</div>';

    echo '<div style="flex:1; min-width:250px; background:#fff; padding:20px; border:1px solid #e2e8f0; border-radius:8px;">';
    echo '<h3>⚙️ Status Sistem</h3>';
    echo '<p><b>API Dripsender:</b> ' . $api_status . '</p>';
    echo '<p><b>Bank Template:</b> ' . intval($total_template) . ' Template Tersimpan</p>';
    echo '</div>';

    echo '<div style="flex:1; min-width:250px; background:#fff; padding:20px; border:1px solid #e2e8f0; border-radius:8px; text-align:center;">';
    echo '<h3>🚀 Jalan Pintas</h3>';
    echo '<a href="?page=wa-modul-riwayat" class="button button-primary" style="width:100%; margin-bottom:10px;">Mulai Broadcast</a>';
    echo '<a href="?page=wa-modul-template" class="button" style="width:100%;">Buat Template Baru</a>';
    echo '</div>';
    
    echo '</div></div>';
}

// =========================================================
// 5. CALLBACK HALAMAN: DATA KONTAK (CRUD & CSV)
// =========================================================
function wa_donatur_data_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wa_donatur';

    // Proses Hapus Kontak
    if (isset($_GET['hapus_id'])) {
        $wpdb->delete($table_name, array('id' => intval($_GET['hapus_id'])));
        echo '<div class="notice notice-success"><p>Kontak berhasil dihapus.</p></div>';
    }

    // Proses Simpan Baru
    if (isset($_POST['submit_manual']) && check_admin_referer('simpan_donatur_manual')) {
        $nama = sanitize_text_field($_POST['nama_donatur']);
        $nomor = wa_format_nomor_hp(sanitize_text_field($_POST['nomor_wa']));
        $kategori = sanitize_text_field($_POST['kategori_wa']);
        
        if (!empty($nama) && !empty($nomor)) {
            $wpdb->insert($table_name, array('nama' => $nama, 'nomor_wa' => $nomor, 'kategori' => $kategori, 'is_subscribed' => 1));
            echo '<div class="notice notice-success"><p>Kontak berhasil ditambahkan!</p></div>';
        }
    }

    // Proses Update (Edit)
    if (isset($_POST['update_manual']) && check_admin_referer('simpan_donatur_manual')) {
        $id = intval($_POST['edit_id']);
        $nama = sanitize_text_field($_POST['nama_donatur']);
        $nomor = wa_format_nomor_hp(sanitize_text_field($_POST['nomor_wa']));
        $kategori = sanitize_text_field($_POST['kategori_wa']);
        
        if (!empty($nama) && !empty($nomor) && $id > 0) {
            $wpdb->update($table_name, array('nama' => $nama, 'nomor_wa' => $nomor, 'kategori' => $kategori), array('id' => $id));
            echo '<div class="notice notice-success"><p>Data kontak berhasil diperbarui!</p></div>';
        }
    }

    // Proses Import CSV
    if (isset($_POST['submit_csv']) && check_admin_referer('simpan_donatur_csv')) {
        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
            $row = 0; $success_count = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($row > 0) { 
                    $nama = sanitize_text_field($data[0]);
                    $nomor = isset($data[1]) ? wa_format_nomor_hp($data[1]) : '';
                    $kategori = isset($data[2]) ? sanitize_text_field($data[2]) : 'donatur';
                    
                    if (!empty($nama) && !empty($nomor)) {
                        $wpdb->insert($table_name, array('nama' => $nama, 'nomor_wa' => $nomor, 'kategori' => $kategori, 'is_subscribed' => 1));
                        $success_count++;
                    }
                }
                $row++;
            }
            fclose($handle);
            echo '<div class="notice notice-success"><p>'.$success_count.' kontak berhasil diimpor!</p></div>';
        }
    }

    // Cek Mode Edit
    $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $edit_data = null;
    if ($edit_id > 0) {
        $edit_data = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $edit_id");
    }

    $form_nama = $edit_data ? esc_attr($edit_data->nama) : '';
    $form_nomor = $edit_data ? esc_attr($edit_data->nomor_wa) : '';
    $form_kat = $edit_data ? esc_attr($edit_data->kategori) : 'donatur';
    $btn_name = $edit_data ? 'update_manual' : 'submit_manual';
    $btn_label = $edit_data ? 'Update Data' : 'Simpan Data';
    
    // Perbaikan URL form action agar mendukung get parameter
    $form_action_url = menu_page_url('wa-modul-donatur', false) . ($edit_id > 0 ? '&edit_id='.$edit_id : '');

    echo '<div class="wrap"><h1>Data Kontak & Kategori</h1>';
    echo '<div style="display:flex; gap: 20px; margin-top: 20px; flex-wrap:wrap;">';
    
    // Form Input / Edit Manual
    echo '<div style="flex:1; min-width:300px; background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:4px;">';
    echo '<h3>' . ($edit_data ? 'Edit Kontak' : 'Tambah Manual') . '</h3><form method="post" action="'. esc_url($form_action_url) .'">';
    wp_nonce_field('simpan_donatur_manual');
    echo '<p><label>Nama Lengkap</label><br><input type="text" name="nama_donatur" value="'.$form_nama.'" required style="width:100%;"></p>';
    echo '<p><label>Nomor WhatsApp</label><br><input type="text" name="nomor_wa" value="'.$form_nomor.'" required style="width:100%;"></p>';
    echo '<p><label>Kategori (Pisahkan dgn koma, cth: donatur, jamaah)</label><br><input type="text" name="kategori_wa" value="'.$form_kat.'" style="width:100%;"></p>';
    
    if ($edit_data) { echo '<input type="hidden" name="edit_id" value="'.$edit_id.'">'; }
    
    echo '<p><input type="submit" name="'.$btn_name.'" class="button button-primary" value="'.$btn_label.'">';
    if ($edit_data) { echo ' <a href="?page=wa-modul-donatur" class="button">Batal Edit</a>'; }
    echo '</p></form></div>';

    // Form Import CSV
    echo '<div style="flex:1; min-width:300px; background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:4px;">';
    echo '<h3>Import via CSV</h3>';
    echo '<p>Format CSV 3 Kolom: <b>Nama</b> | <b>Nomor WA</b> | <b>Kategori</b> (Baris 1 diabaikan).</p>';
    echo '<form method="post" action="?page=wa-modul-donatur" enctype="multipart/form-data">';
    wp_nonce_field('simpan_donatur_csv');
    echo '<p><input type="file" name="csv_file" accept=".csv" required></p>';
    echo '<p><input type="submit" name="submit_csv" class="button button-secondary" value="Import File CSV"></p></form></div>';
    
    echo '</div>'; 

    // Tabel Kontak
    echo '<h2 style="margin-top:40px;">Daftar Kontak Tersimpan</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Nama</th><th>Nomor WA</th><th>Kategori</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead><tbody>';
    
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 100");
    if ($results) {
        foreach ($results as $r) {
            $status = $r->is_subscribed ? '<span style="color:green;">Aktif</span>' : '<span style="color:red;">Opt-Out</span>';
            echo '<tr><td>'.esc_html($r->nama).'</td><td>'.esc_html($r->nomor_wa).'</td><td><b>'.esc_html($r->kategori).'</b></td><td>'.$status.'</td><td>'.esc_html($r->tanggal_dibuat).'</td>';
            echo '<td><a href="?page=wa-modul-donatur&edit_id='.$r->id.'" class="button button-small">Edit</a> ';
            echo '<a href="?page=wa-modul-donatur&hapus_id='.$r->id.'" class="button button-small" style="color:red; border-color:red;" onclick="return confirm(\'Yakin hapus kontak ini?\')">Hapus</a></td></tr>';
        }
    } else { echo '<tr><td colspan="6">Belum ada data.</td></tr>'; }
    echo '</tbody></table></div>';
}

// =========================================================
// 6. CALLBACK HALAMAN: TEMPLATE PESAN (CRUD List Template)
// =========================================================
function wa_donatur_template_callback() {
    global $wpdb;
    $table_template = $wpdb->prefix . 'wa_templates';

    // Proses Hapus
    if (isset($_GET['hapus_id'])) {
        $wpdb->delete($table_template, array('id' => intval($_GET['hapus_id'])));
        echo '<div class="notice notice-success"><p>Template berhasil dihapus.</p></div>';
    }

    // Proses Simpan Baru
    if (isset($_POST['simpan_template']) && check_admin_referer('simpan_template_wa')) {
        $judul = sanitize_text_field($_POST['judul_template']);
        $isi = sanitize_textarea_field($_POST['template_pesan']);
        if(!empty($judul) && !empty($isi)) {
            $wpdb->insert($table_template, array('judul' => $judul, 'isi_template' => $isi));
            echo '<div class="notice notice-success"><p>Template baru berhasil disimpan!</p></div>';
        }
    }

    // Proses Update (Edit)
    if (isset($_POST['update_template']) && check_admin_referer('simpan_template_wa')) {
        $id = intval($_POST['edit_tpl_id']);
        $judul = sanitize_text_field($_POST['judul_template']);
        $isi = sanitize_textarea_field($_POST['template_pesan']);
        if(!empty($judul) && !empty($isi) && $id > 0) {
            $wpdb->update($table_template, array('judul' => $judul, 'isi_template' => $isi), array('id' => $id));
            echo '<div class="notice notice-success"><p>Template berhasil diperbarui!</p></div>';
        }
    }

    // Cek Mode Edit
    $edit_tpl_id = isset($_GET['edit_tpl_id']) ? intval($_GET['edit_tpl_id']) : 0;
    $edit_tpl = null;
    if ($edit_tpl_id > 0) {
        $edit_tpl = $wpdb->get_row("SELECT * FROM $table_template WHERE id = $edit_tpl_id");
    }

    $tpl_judul = $edit_tpl ? esc_attr($edit_tpl->judul) : '';
    $tpl_isi = $edit_tpl ? esc_textarea($edit_tpl->isi_template) : '';
    $tpl_btn_name = $edit_tpl ? 'update_template' : 'simpan_template';
    $tpl_btn_label = $edit_tpl ? 'Update Template' : 'Simpan ke Bank Template';
    
    // Perbaikan URL form action agar mendukung get parameter
    $tpl_action_url = menu_page_url('wa-modul-template', false) . ($edit_tpl_id > 0 ? '&edit_tpl_id='.$edit_tpl_id : '');

    echo '<div class="wrap"><h1>Bank Template Pesan</h1>';
    
    // Form Input / Edit Template
    echo '<div style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:4px; max-width:800px; margin-top:15px;">';
    echo '<h3>' . ($edit_tpl ? 'Edit Template' : 'Buat Template Baru') . '</h3>';
    echo '<form method="post" action="'. esc_url($tpl_action_url) .'">';
    wp_nonce_field('simpan_template_wa');
    echo '<p><label>Judul Template (Penanda saja)</label><br><input type="text" name="judul_template" value="'.$tpl_judul.'" placeholder="Cth: Laporan Kas Bulanan" required style="width:100%;"></p>';
    echo '<p><label>Isi Pesan (Gunakan: <code>{{nama_donatur}}</code>, <code>{{saldo_terkini}}</code>, <code>{{masuk_bulan_ini}}</code>, <code>{{keluar_bulan_ini}}</code>)</label><br>';
    echo '<textarea name="template_pesan" rows="6" required style="width:100%;">'.$tpl_isi.'</textarea></p>';
    
    if ($edit_tpl) { echo '<input type="hidden" name="edit_tpl_id" value="'.$edit_tpl_id.'">'; }

    echo '<p><input type="submit" name="'.$tpl_btn_name.'" class="button button-primary" value="'.$tpl_btn_label.'">';
    if ($edit_tpl) { echo ' <a href="?page=wa-modul-template" class="button">Batal Edit</a>'; }
    echo '</p></form></div>';

    // Tabel Daftar Template
    echo '<h3 style="margin-top:40px;">Daftar Template Tersimpan</h3>';
    echo '<table class="wp-list-table widefat fixed striped" style="max-width:800px;">';
    echo '<thead><tr><th style="width:30%;">Judul Template</th><th>Cuplikan Isi</th><th style="width:20%;">Aksi</th></tr></thead><tbody>';
    
    $templates = $wpdb->get_results("SELECT * FROM $table_template ORDER BY id DESC");
    if ($templates) {
        foreach ($templates as $t) {
            $cuplikan = substr($t->isi_template, 0, 80) . '...';
            echo '<tr>';
            echo '<td><strong>' . esc_html($t->judul) . '</strong></td>';
            echo '<td>' . esc_html($cuplikan) . '</td>';
            echo '<td><a href="?page=wa-modul-template&edit_tpl_id='.$t->id.'" class="button button-small">Edit</a> ';
            echo '<a href="?page=wa-modul-template&hapus_id='.$t->id.'" class="button button-small" style="color:red; border-color:red;" onclick="return confirm(\'Yakin hapus template ini?\')">Hapus</a></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="3">Belum ada template tersimpan.</td></tr>';
    }
    echo '</tbody></table></div>';
}

// =========================================================
// 7. CALLBACK HALAMAN: MESIN BROADCAST (Filter Kategori)
// =========================================================
function wa_donatur_riwayat_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wa_donatur';
    $table_template = $wpdb->prefix . 'wa_templates';

    $donaturs = $wpdb->get_results("SELECT id, nama, nomor_wa, kategori FROM $table_name WHERE is_subscribed = 1");
    $templates = $wpdb->get_results("SELECT id, judul FROM $table_template ORDER BY id DESC");
    
    // Ambil list kategori unik
    $kategori_list = array();
    foreach($donaturs as $d) {
        $cats = explode(',', $d->kategori);
        foreach($cats as $c) {
            $c = trim($c);
            if(!empty($c) && !in_array($c, $kategori_list)) { $kategori_list[] = $c; }
        }
    }
    sort($kategori_list);

    echo '<div class="wrap"><h1>Mesin Broadcast Cerdas</h1>';
    
    if (empty($templates)) {
        echo '<div class="notice notice-warning"><p>Bank Template masih kosong. Buat template dulu di menu <b>Template Pesan</b>.</p></div></div>'; return;
    }
    if (empty($donaturs)) {
        echo '<div class="notice notice-warning"><p>Belum ada kontak aktif.</p></div></div>'; return;
    }

    echo '<div style="background:#fff; padding:20px; border:1px solid #ccc; border-radius:5px; max-width:800px; margin-top:20px;">';
    
    // Form Filter Setup
    echo '<h3>⚙️ Pengaturan Pengiriman</h3>';
    echo '<div style="display:flex; gap:20px; margin-bottom:20px;">';
    
    echo '<div style="flex:1;"><b>1. Pilih Kategori Tujuan:</b><br>';
    echo '<select id="filter-kategori" style="width:100%; margin-top:5px;">';
    echo '<option value="all">-- Semua Kategori (' . count($donaturs) . ' Kontak) --</option>';
    foreach($kategori_list as $kat) { echo '<option value="'.esc_attr($kat).'">Kategori: '.esc_html($kat).'</option>'; }
    echo '</select></div>';

    echo '<div style="flex:1;"><b>2. Pilih Template Pesan:</b><br>';
    echo '<select id="filter-template" style="width:100%; margin-top:5px;">';
    echo '<option value="">-- Pilih Template --</option>';
    foreach($templates as $tpl) { echo '<option value="'.esc_attr($tpl->id).'">'.esc_html($tpl->judul).'</option>'; }
    echo '</select></div>';
    
    echo '</div>';

    echo '<button id="btn-start-broadcast" class="button button-primary button-large" style="width:100%;">🚀 Mulai Broadcast Sesuai Filter</button>';
    
    // Wadah Progress
    echo '<div id="broadcast-progress-container" style="display:none; margin-top:30px;">';
    echo '<h4>Progres Pengiriman: <span id="progress-text">0 / 0</span></h4>';
    echo '<progress id="broadcast-progress" value="0" max="100" style="width:100%; height:25px;"></progress>';
    echo '<h4 style="margin-top:20px;">Terminal Log:</h4>';
    echo '<div id="broadcast-log" style="background:#1e1e1e; color:#0f0; padding:15px; height:300px; overflow-y:auto; font-family:monospace; border-radius:4px;"></div>';
    echo '</div></div>';

    $donatur_json = json_encode($donaturs);
    $nonce = wp_create_nonce("broadcast_wa_nonce");
    ?>
    
    <script>
    jQuery(document).ready(function($) {
        var allDonaturs = <?php echo $donatur_json; ?>;
        var targetDonaturs = [];
        var total = 0; var current = 0; var templateId = 0;

        $('#btn-start-broadcast').on('click', function() {
            var selectedKat = $('#filter-kategori').val();
            templateId = $('#filter-template').val();
            
            if(!templateId) { alert('Silakan pilih Template Pesan terlebih dahulu!'); return; }

            // Filter data kontak berdasarkan kategori multi-tag
            targetDonaturs = allDonaturs.filter(function(d) {
                if (selectedKat === 'all') return true;
                var katArray = d.kategori.split(',').map(function(item) { return item.trim().toLowerCase(); });
                return katArray.indexOf(selectedKat.toLowerCase()) !== -1;
            });

            total = targetDonaturs.length;

            if(total === 0) { alert('Tidak ada kontak di kategori ini.'); return; }
            if(!confirm('Ditemukan ' + total + ' kontak. Mulai pengiriman sekarang?')) return;
            
            $(this).prop('disabled', true).text('Sedang Memproses...');
            $('#filter-kategori, #filter-template').prop('disabled', true);
            
            $('#broadcast-progress').attr('max', total);
            $('#broadcast-progress-container').fadeIn();
            $('#broadcast-log').append('<p style="color:#fff;">[SISTEM] Memulai antrean ke ' + total + ' kontak...</p>');
            
            processQueue(); 
        });

        function processQueue() {
            if (current >= total) {
                $('#broadcast-log').append('<p style="color:#0f0;">[SELESAI] Alhamdulillah, semua pesan telah diproses!</p>');
                $('#btn-start-broadcast').text('Tugas Selesai');
                scrollToBottom(); return;
            }

            var donatur = targetDonaturs[current];
            var delay = Math.floor(Math.random() * (10000 - 5000 + 1) + 5000); 

            $('#broadcast-log').append('<p style="color:#fff;">[MENGIRIM] Menyiapkan pesan ke: ' + donatur.nama + '...</p>');
            scrollToBottom();

            $.ajax({
                url: ajaxurl, type: 'POST',
                data: {
                    action: 'send_wa_broadcast_ajax',
                    nama: donatur.nama, nomor_wa: donatur.nomor_wa,
                    template_id: templateId,
                    security: '<?php echo $nonce; ?>'
                },
                success: function(res) {
                    if (res.success) { $('#broadcast-log').append('<p style="color:#0f0;">[BERHASIL] Terkirim ke ' + donatur.nama + '</p>'); } 
                    else { $('#broadcast-log').append('<p style="color:#f00;">[GAGAL] ' + donatur.nama + ' - ' + res.data + '</p>'); }
                },
                error: function() { $('#broadcast-log').append('<p style="color:#f00;">[ERROR] Koneksi terputus ke ' + donatur.nama + '</p>'); },
                complete: function() {
                    current++;
                    $('#progress-text').text(current + ' / ' + total);
                    $('#broadcast-progress').val(current);
                    scrollToBottom();
                    
                    if (current < total) {
                        $('#broadcast-log').append('<p style="color:#aaa;">[JEDA] Menunggu ' + (delay/1000).toFixed(1) + ' detik...</p><br>');
                        setTimeout(processQueue, delay);
                    } else { processQueue(); }
                }
            });
        }
        function scrollToBottom() { var log = $('#broadcast-log'); log.scrollTop(log[0].scrollHeight); }
    });
    </script>
    <?php echo '</div>'; 
}

// =========================================================
// 8. FUNGSI AJAX: EKSEKUTOR (Ambil Data Kas & Tembak API)
// =========================================================
add_action('wp_ajax_send_wa_broadcast_ajax', 'ajax_send_wa_broadcast_handler');

function ajax_send_wa_broadcast_handler() {
    global $wpdb;
    check_ajax_referer('broadcast_wa_nonce', 'security');

    $nama = sanitize_text_field($_POST['nama']);
    $nomor = sanitize_text_field($_POST['nomor_wa']);
    $template_id = intval($_POST['template_id']);
    
    $webhook_url = get_option('wa_dripsender_webhook');
    $api_token   = get_option('wa_dripsender_token');

    // Tarik teks template berdasarkan ID yang dipilih
    $table_template = $wpdb->prefix . 'wa_templates';
    $row_tpl = $wpdb->get_row($wpdb->prepare("SELECT isi_template FROM $table_template WHERE id = %d", $template_id));
    if(!$row_tpl) { wp_send_json_error('Template tidak ditemukan di database.'); }
    $template_text = $row_tpl->isi_template;

    if (empty($webhook_url) || empty($api_token)) { wp_send_json_error('API Key kosong.'); }

    // Hitung Laporan Kas dari Google Script (Cache 5 Menit)
    $laporan_kas = get_transient('wa_laporan_kas_data');
    if (false === $laporan_kas) {
        $api_kas_url = 'https://script.google.com/macros/s/AKfycbzIDUg1PQd_Naiby7-uAzVKrTCiVZMOIqmXSz1aLyHHZZKWw_acsMk5zqrICg3yw2G1vw/exec';
        $res_kas = wp_remote_get($api_kas_url, array('timeout' => 15));
        
        $saldo = 0; $masuk_bln = 0; $keluar_bln = 0;
        if (!is_wp_error($res_kas)) {
            $data = json_decode(wp_remote_retrieve_body($res_kas), true);
            $c_month = date('n'); $c_year = date('Y');  
            if (is_array($data)) {
                foreach ($data as $row) {
                    $m = isset($row['masuk']) ? floatval($row['masuk']) : 0;
                    $k = isset($row['keluar']) ? floatval($row['keluar']) : 0;
                    $t = isset($row['tanggal']) ? strtotime($row['tanggal']) : 0;
                    $saldo += ($m - $k);
                    if ($t && date('n', $t) == $c_month && date('Y', $t) == $c_year) { $masuk_bln += $m; $keluar_bln += $k; }
                }
            }
        }
        $laporan_kas = array(
            'saldo' => number_format($saldo, 0, ',', '.'),
            'masuk' => number_format($masuk_bln, 0, ',', '.'),
            'keluar'=> number_format($keluar_bln, 0, ',', '.')
        );
        set_transient('wa_laporan_kas_data', $laporan_kas, 300);
    }

    // Ganti Placeholder Text
    $pesan_final = str_replace(
        array('{{nama_donatur}}', '{{saldo_terkini}}', '{{masuk_bulan_ini}}', '{{keluar_bulan_ini}}'),
        array($nama, $laporan_kas['saldo'], $laporan_kas['masuk'], $laporan_kas['keluar']),
        $template_text
    );

    $args = array(
        'body' => json_encode(array('api_key' => $api_token, 'phone' => $nomor, 'text' => $pesan_final)),
        'timeout' => 15, 'blocking' => true,
        'headers' => array('Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_token),
    );

    $response = wp_remote_post($webhook_url, $args);
    if (is_wp_error($response)) { wp_send_json_error($response->get_error_message()); } 
    else {
        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code == 200 || $http_code == 201) { wp_send_json_success('Terkirim'); } 
        else { wp_send_json_error('Code: ' . $http_code); }
    }
}

// =========================================================
// 9. CALLBACK HALAMAN: SETTING INTEGRASI & PING
// =========================================================
function wa_donatur_setting_callback() {
    if (isset($_POST['simpan_setting']) && check_admin_referer('simpan_setting_wa')) {
        update_option('wa_dripsender_webhook', sanitize_url($_POST['dripsender_webhook']));
        update_option('wa_dripsender_token', sanitize_text_field($_POST['dripsender_token']));
        echo '<div class="notice notice-success"><p>Pengaturan disimpan!</p></div>';
    }

    if (isset($_POST['test_ping']) && check_admin_referer('test_ping_wa')) {
        $test_number = sanitize_text_field($_POST['test_nomor']);
        $webhook_url = get_option('wa_dripsender_webhook');
        $api_token   = get_option('wa_dripsender_token');

        if (empty($webhook_url) || empty($api_token)) { echo '<div class="notice notice-error"><p>Gagal: Token kosong.</p></div>'; } 
        else {
            $args = array(
                'body' => json_encode(array('api_key' => $api_token, 'phone' => wa_format_nomor_hp($test_number), 'text' => "Ping dari sistem website berhasil!")),
                'timeout' => 15, 'headers' => array('Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_token)
            );
            $resp = wp_remote_post($webhook_url, $args);
            if(is_wp_error($resp)){ echo '<div class="notice notice-error"><p>Error: '.$resp->get_error_message().'</p></div>'; }
            else { echo '<div class="notice notice-success"><p>Berhasil! HTTP Code: '.wp_remote_retrieve_response_code($resp).'</p></div>'; }
        }
    }

    $saved_webhook = get_option('wa_dripsender_webhook', 'https://api.dripsender.id/send');
    $saved_token = get_option('wa_dripsender_token', '');

    echo '<div class="wrap"><h1>Setting API</h1><div style="display:flex; gap:20px; margin-top:20px;">';
    echo '<div style="flex:2; background:#fff; padding:20px; border:1px solid #ccd0d4;"><form method="post" action="">';
    wp_nonce_field('simpan_setting_wa');
    echo '<p><label>Webhook / API URL</label><br><input type="url" name="dripsender_webhook" value="'.esc_attr($saved_webhook).'" style="width:100%;"></p>';
    echo '<p><label>API Token Dripsender</label><br><input type="text" name="dripsender_token" value="'.esc_attr($saved_token).'" style="width:100%;"></p>';
    echo '<p><input type="submit" name="simpan_setting" class="button button-primary" value="Simpan"></p></form></div>';

    echo '<div style="flex:1; background:#f0f6fc; padding:20px; border:1px solid #c8d7e1;"><form method="post" action="">';
    wp_nonce_field('test_ping_wa');
    echo '<p><label>Nomor Test Ping</label><br><input type="text" name="test_nomor" required style="width:100%;"></p>';
    echo '<p><input type="submit" name="test_ping" class="button button-secondary" value="Kirim Ping"></p></form></div>';
    echo '</div></div>';
}
