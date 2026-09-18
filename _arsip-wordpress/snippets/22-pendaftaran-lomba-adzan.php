<?php
// SNIPPET WordPress #22: PENDAFTARAN LOMBA ADZAN
// scope: global | status: nonaktif | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

/* * ==========================================================
 * 1. MEMBUAT CUSTOM POST TYPE (WADAH DATA PENDAFTAR)
 * ==========================================================
 */
add_action('init', 'ksk_register_cpt_adzan');
function ksk_register_cpt_adzan() {
    $labels = array(
        'name'               => 'Pendaftar Adzan',
        'singular_name'      => 'Pendaftar Adzan',
        'add_new'            => 'Tambah Data Peserta',
        'add_new_item'       => 'Formulir Data Peserta Baru',
        'edit_item'          => 'Edit Data Peserta',
        'all_items'          => 'Semua Peserta',
        'search_items'       => 'Cari Peserta',
    );

    $args = array(
        'labels'       => $labels,
        'public'       => true,
        'menu_icon'    => 'dashicons-microphone', // Ikon mic di dashboard
        'show_in_menu' => true,
        'supports'     => array('thumbnail'), 
        'has_archive'  => false,
    );
    register_post_type('pendaftar_adzan', $args);
}

// Menyembunyikan metabox "Featured Image" (Gambar Unggulan) bawaan WP di sidebar kanan
add_action('do_meta_boxes', 'ksk_remove_thumbnail_box');
function ksk_remove_thumbnail_box() {
    remove_meta_box('postimagediv', 'pendaftar_adzan', 'side');
}

/* * ==========================================================
 * 2. MENGATUR TABEL KOLOM DI DASHBOARD (AGAR RAPI & PRESISI)
 * ==========================================================
 */
// A. Custom CSS untuk mempercantik tabel Dashboard
add_action('admin_head', 'ksk_admin_custom_css');
function ksk_admin_custom_css() {
    global $typenow;
    if ($typenow == 'pendaftar_adzan') {
        echo '<style>
            /* Mengatur proporsi lebar masing-masing kolom agar tidak berantakan */
            .wp-list-table th.column-cb { width: 3%; }
            .wp-list-table th.column-title { width: 17%; }
            .wp-list-table th.column-asal_lembaga { width: 15%; }
            .wp-list-table th.column-kategori { width: 9%; text-align: center; }
            .wp-list-table th.column-no_wa { width: 13%; }
            .wp-list-table th.column-link_video { width: 10%; text-align: center; }
            .wp-list-table th.column-bukti_tf { width: 10%; text-align: center; }
            .wp-list-table th.column-date { width: 10%; }
            .wp-list-table th.column-aksi_cepat { width: 13%; text-align: center; }
            
            /* Mempercantik elemen data di dalam tabel */
            .badge-kat { background: #e0f2fe; color: #0369a1; padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 12px; display: inline-block; }
            .badge-lembaga { background: #f8fafc; color: #334155; padding: 5px 8px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; display: inline-block; line-height: 1.4; }
            
            /* Mengatur tombol aksi menjadi bertumpuk vertikal dan seragam */
            .btn-aksi { display: block; box-sizing: border-box; width: 100%; padding: 6px 10px; margin-bottom: 5px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: 600; color: #fff !important; text-align: center; transition: 0.2s; }
            .btn-verif { background: #10b981; border: 1px solid #059669; }
            .btn-verif:hover { background: #059669; transform: translateY(-1px); }
            .btn-sah { background: #f1f5f9; color: #64748b !important; border: 1px solid #cbd5e1; cursor: default; }
            .btn-edit { background: #3b82f6; border: 1px solid #2563eb; }
            .btn-edit:hover { background: #2563eb; transform: translateY(-1px); }
            .btn-hapus { background: #ef4444; border: 1px solid #dc2626; }
            .btn-hapus:hover { background: #dc2626; transform: translateY(-1px); }
        </style>';
    }
}

// B. Mendefinisikan Kolom
add_filter('manage_pendaftar_adzan_posts_columns', 'ksk_set_custom_columns');
function ksk_set_custom_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = 'Nama Anak';
    $new_columns['asal_lembaga'] = 'Asal Lembaga';
    $new_columns['kategori'] = 'Kategori';
    $new_columns['no_wa'] = 'No. WhatsApp';
    $new_columns['link_video'] = 'Karya Video';
    $new_columns['bukti_tf'] = 'Bukti Infaq';
    $new_columns['date'] = 'Tanggal Daftar';
    $new_columns['aksi_cepat'] = 'Manajemen Data'; // Kolom aksi dipindah ke paling kanan
    return $new_columns;
}

// C. Mengisi Kolom dengan Data & Tombol
add_action('manage_pendaftar_adzan_posts_custom_column', 'ksk_fill_custom_columns', 10, 2);
function ksk_fill_custom_columns($column, $post_id) {
    switch ($column) {
        case 'kategori':
            $kat = esc_html(get_post_meta($post_id, 'kategori', true));
            echo '<div style="text-align: center;"><span class="badge-kat">Kelas ' . $kat . '</span></div>';
            break;
        case 'asal_lembaga':
            $lembaga = esc_html(get_post_meta($post_id, 'asal_lembaga', true));
            echo '<span class="badge-lembaga"><span class="dashicons dashicons-bank" style="font-size:14px; margin-top:2px;"></span> ' . $lembaga . '</span>';
            break;
        case 'no_wa':
            $wa = esc_html(get_post_meta($post_id, 'no_wa', true));
            if ($wa) {
                echo '<a href="https://wa.me/62' . substr($wa, 1) . '" target="_blank" style="font-weight:700; color:#16a34a; text-decoration:none;"><span class="dashicons dashicons-whatsapp"></span> '.$wa.'</a>';
            } else {
                echo '-';
            }
            break;
        case 'link_video':
            $link = esc_url(get_post_meta($post_id, 'link_video', true));
            if ($link) {
                echo '<div style="text-align: center;"><a href="' . $link . '" target="_blank" class="button button-small" style="border-radius:20px;"><span class="dashicons dashicons-video-alt3" style="margin-top:4px;"></span> Buka Video</a></div>';
            } else {
                echo '<div style="text-align: center;">-</div>';
            }
            break;
        case 'bukti_tf':
            if (has_post_thumbnail($post_id)) {
                $img_url = get_the_post_thumbnail_url($post_id, 'full');
                echo '<div style="text-align: center;"><a href="' . $img_url . '" target="_blank" title="Klik untuk perbesar">';
                echo get_the_post_thumbnail($post_id, array(60, 60), array('style' => 'border-radius:6px; border:2px solid #e2e8f0; object-fit:cover; height:60px;'));
                echo '</a></div>';
            } else {
                echo '<div style="text-align: center;"><span style="color:#ef4444; font-weight:700; font-size:12px; background:#fef2f2; padding:3px 8px; border-radius:4px;">Belum Ada</span></div>';
            }
            break;
        case 'aksi_cepat':
            $status = get_post_status($post_id);
            $edit_link = get_edit_post_link($post_id);
            $delete_link = get_delete_post_link($post_id);
            
            // Tombol Verifikasi
            if ($status == 'pending') {
                $verif_url = wp_nonce_url(admin_url('admin-ajax.php?action=ksk_verifikasi_peserta&post_id=' . $post_id), 'ksk_verif_' . $post_id);
                echo '<a href="' . $verif_url . '" class="btn-aksi btn-verif" title="Tandai pembayaran sah">✓ Verifikasi Sah</a>';
            } else {
                echo '<span class="btn-aksi btn-sah">✓ Status Lunas</span>';
            }

            // Tombol Edit
            echo '<a href="' . $edit_link . '" class="btn-aksi btn-edit">✎ Edit Data</a>';
            
            // Tombol Hapus 
            echo '<a href="' . $delete_link . '" class="btn-aksi btn-hapus" onclick="return confirm(\'Yakin ingin menghapus peserta ini secara permanen?\')">🗑 Hapus</a>';
            break;
    }
}

// D. Fungsi Ajax untuk Tombol Verifikasi
add_action('wp_ajax_ksk_verifikasi_peserta', 'ksk_verifikasi_peserta_action');
function ksk_verifikasi_peserta_action() {
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    
    if ($post_id && check_admin_referer('ksk_verif_' . $post_id)) {
        $post = array(
            'ID'          => $post_id,
            'post_status' => 'publish' 
        );
        wp_update_post($post);
    }
    
    wp_redirect(admin_url('edit.php?post_type=pendaftar_adzan'));
    exit;
}

/* * ==========================================================
 * 3. MEMBUAT FORMULIR ISIAN MANUAL DI DALAM DASHBOARD (META BOX)
 * ==========================================================
 */
add_action('add_meta_boxes', 'ksk_add_meta_boxes');
function ksk_add_meta_boxes() {
    add_meta_box('ksk_pendaftar_data', 'Detail Isian Data Peserta', 'ksk_render_meta_box', 'pendaftar_adzan', 'normal', 'high');
}

function ksk_render_meta_box($post) {
    wp_nonce_field('ksk_save_meta_box_data', 'ksk_meta_box_nonce');

    $nama_anak = get_post_meta($post->ID, 'nama_anak', true);
    $tempat_lahir = get_post_meta($post->ID, 'tempat_lahir', true);
    $tanggal_lahir = get_post_meta($post->ID, 'tanggal_lahir', true);
    $asal_lembaga = get_post_meta($post->ID, 'asal_lembaga', true);
    $kategori = get_post_meta($post->ID, 'kategori', true);
    $nama_pendaftar = get_post_meta($post->ID, 'nama_pendaftar', true);
    $no_wa = get_post_meta($post->ID, 'no_wa', true);
    $link_video = get_post_meta($post->ID, 'link_video', true);

    echo '<table class="form-table"><tbody>';
    
    echo '<tr><th><label>Nama Lengkap Anak</label></th><td>';
    echo '<input type="text" name="nama_anak" value="' . esc_attr($nama_anak) . '" class="large-text" required>';
    echo '</td></tr>';

    echo '<tr><th><label>Asal Lembaga</label></th><td>';
    echo '<input type="text" name="asal_lembaga" value="' . esc_attr($asal_lembaga) . '" class="regular-text" required>';
    echo '</td></tr>';
    
    echo '<tr><th><label>Kategori Lomba</label></th><td>';
    echo '<select name="kategori">';
    echo '<option value="A" ' . selected($kategori, 'A', false) . '>Kategori A (Kelas 1-3 SD)</option>';
    echo '<option value="B" ' . selected($kategori, 'B', false) . '>Kategori B (Kelas 4-6 SD)</option>';
    echo '</select></td></tr>';

    echo '<tr><th><label>Tempat Lahir</label></th><td>';
    echo '<input type="text" name="tempat_lahir" value="' . esc_attr($tempat_lahir) . '" class="regular-text">';
    echo '</td></tr>';

    echo '<tr><th><label>Tanggal Lahir</label></th><td>';
    echo '<input type="date" name="tanggal_lahir" value="' . esc_attr($tanggal_lahir) . '" class="regular-text">';
    echo '</td></tr>';
    
    echo '<tr><th><label>Nama Orang Tua/Wali</label></th><td>';
    echo '<input type="text" name="nama_pendaftar" value="' . esc_attr($nama_pendaftar) . '" class="regular-text">';
    echo '</td></tr>';
    
    echo '<tr><th><label>No. WhatsApp</label></th><td>';
    echo '<input type="text" name="no_wa" value="' . esc_attr($no_wa) . '" class="regular-text">';
    echo '</td></tr>';

    echo '<tr><th><label>Link Video Adzan</label></th><td>';
    echo '<input type="url" name="link_video" value="' . esc_attr($link_video) . '" class="large-text">';
    echo '<p class="description">URL YouTube (Unlisted) atau Google Drive.</p></td></tr>';

    echo '<tr style="background:#f8fafc;"><th><label>Bukti Transfer Infaq</label></th><td style="padding:15px;">';
    
    $thumbnail_id = get_post_thumbnail_id($post->ID);
    if ($thumbnail_id) {
        echo '<div style="margin-bottom:15px; border:2px solid #10b981; display:inline-block; padding:5px; background:#fff;">';
        echo wp_get_attachment_image($thumbnail_id, 'medium');
        echo '</div><br>';
    } else {
        echo '<p style="color:#d63638; font-weight:bold;">Belum ada bukti transfer.</p>';
    }

    echo '<input type="file" name="bukti_transfer_admin" accept="image/jpeg, image/png"><br>';
    echo '<p class="description">Unggah gambar baru untuk mengganti/menambahkan bukti transfer (Maks 2MB).</p>';
    echo '</td></tr>';

    echo '</tbody></table>';
}

// Menyimpan Data & Mengotomatiskan Title Database
add_action('save_post', 'ksk_save_meta_box_data', 10, 2);
function ksk_save_meta_box_data($post_id, $post) {
    if (!isset($_POST['ksk_meta_box_nonce']) || !wp_verify_nonce($_POST['ksk_meta_box_nonce'], 'ksk_save_meta_box_data')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = array('nama_anak', 'tempat_lahir', 'tanggal_lahir', 'asal_lembaga', 'kategori', 'nama_pendaftar', 'no_wa', 'link_video');
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    if (!empty($_FILES['bukti_transfer_admin']) && $_FILES['bukti_transfer_admin']['error'] === UPLOAD_ERR_OK) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_handle_upload('bukti_transfer_admin', $post_id);

        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }

    $nama = isset($_POST['nama_anak']) && !empty($_POST['nama_anak']) ? sanitize_text_field($_POST['nama_anak']) : 'Tanpa Nama';
    
    $new_title = $nama;

    remove_action('save_post', 'ksk_save_meta_box_data');

    wp_update_post(array(
        'ID'         => $post_id,
        'post_title' => $new_title,
        'post_name'  => sanitize_title($new_title) 
    ));

    add_action('save_post', 'ksk_save_meta_box_data', 10, 2);
}

/* * ==========================================================
 * 4. MENANGANI DATA YANG DIKIRIM DARI FORMULIR FRONT-END (ELEMENTOR AJAX)
 * ==========================================================
 */
add_action('wp_ajax_submit_form_adzan', 'ksk_handle_submit_adzan');
add_action('wp_ajax_nopriv_submit_form_adzan', 'ksk_handle_submit_adzan');
function ksk_handle_submit_adzan() {
    if (empty($_POST['nama_anak'])) {
        wp_send_json_error('Data nama tidak boleh kosong.');
    }

    $nama_anak = sanitize_text_field($_POST['nama_anak']);
    $asal_lembaga = sanitize_text_field($_POST['asal_lembaga']);

    $post_id = wp_insert_post(array(
        'post_title'  => $nama_anak,
        'post_type'   => 'pendaftar_adzan',
        'post_status' => 'pending', 
    ));

    if (is_wp_error($post_id)) {
        wp_send_json_error('Sistem gagal menyimpan data.');
    }

    update_post_meta($post_id, 'nama_anak', $nama_anak);
    update_post_meta($post_id, 'tempat_lahir', sanitize_text_field($_POST['tempat_lahir']));
    update_post_meta($post_id, 'tanggal_lahir', sanitize_text_field($_POST['tanggal_lahir']));
    update_post_meta($post_id, 'asal_lembaga', $asal_lembaga);
    update_post_meta($post_id, 'kategori', sanitize_text_field($_POST['kategori']));
    update_post_meta($post_id, 'nama_pendaftar', sanitize_text_field($_POST['nama_pendaftar']));
    update_post_meta($post_id, 'no_wa', sanitize_text_field($_POST['no_wa']));
    update_post_meta($post_id, 'link_video', esc_url_raw($_POST['link_video']));

    if (!empty($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === UPLOAD_ERR_OK) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_handle_upload('bukti_transfer', $post_id);

        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }

    wp_send_json_success('Pendaftaran berhasil disimpan.');
}

/* * ==========================================================
 * 5. MENYEDIAKAN DATA UNTUK LIVE COUNTER & DAFTAR LEMBAGA (FRONT-END)
 * ==========================================================
 */
add_action('wp_ajax_get_stats_adzan', 'ksk_get_stats_adzan');
add_action('wp_ajax_nopriv_get_stats_adzan', 'ksk_get_stats_adzan');
function ksk_get_stats_adzan() {
    $counts = wp_count_posts('pendaftar_adzan');
    $total = (isset($counts->pending) ? $counts->pending : 0) + (isset($counts->publish) ? $counts->publish : 0);

    global $wpdb;
    $query = $wpdb->prepare("
        SELECT DISTINCT pm.meta_value 
        FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = 'asal_lembaga' 
        AND p.post_type = 'pendaftar_adzan' 
        AND p.post_status IN ('publish', 'pending')
        AND pm.meta_value != ''
    ");
    $results = $wpdb->get_col($query);
    
    if (!empty($results)) { sort($results); } 
    else { $results = array(); }

    wp_send_json_success(array('total' => $total, 'lembaga' => $results));
}

/* * ==========================================================
 * 6. MEMBUAT MENU SUB-PAGE "PENILAIAN JURI" (BACKEND) & ROLE USTADZ
 * ==========================================================
 */
add_action('init', 'ksk_tambah_role_ustadz');
function ksk_tambah_role_ustadz() {
    if (!get_role('ustadz')) {
        add_role('ustadz', 'Ustadz / Juri', array('read' => true));
    }
}

add_action('admin_menu', 'ksk_add_juri_submenu');
function ksk_add_juri_submenu() {
    add_submenu_page(
        'edit.php?post_type=pendaftar_adzan', 
        'Meja Penilaian Juri',                
        'Penilaian Juri',                     
        'read',                               
        'penilaian_juri_adzan',               
        'ksk_render_halaman_juri_backend'             
    );
}

function ksk_render_halaman_juri_backend() {
    $current_juri_id = get_current_user_id();

    $peserta = get_posts(array(
        'post_type'      => 'pendaftar_adzan',
        'post_status'    => 'publish', 
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC'
    ));

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline"><span class="dashicons dashicons-welcome-write-blog" style="margin-top:5px; margin-right:10px;"></span> Meja Penilaian Juri Lomba Adzan</h1>';
    echo '<p style="background:#fff; padding:15px; border-left:4px solid #10b981;">Hanya menampilkan peserta yang sudah diverifikasi pembayarannya.</p>';

    echo '<table class="wp-list-table widefat fixed striped" style="margin-top:20px;">';
    echo '<thead><tr>';
    echo '<th style="width:5%;">No</th>';
    echo '<th style="width:20%;">Nama Anak</th>';
    echo '<th style="text-align:center;">Kategori</th>';
    echo '<th style="text-align:center;">Karya Video</th>';
    echo '<th style="text-align:center;">Nilai & Catatan Anda</th>';
    echo '<th style="text-align:center; background:#f0fdf4;">Skor Akhir (Rata-rata Semua Juri)</th>';
    echo '</tr></thead><tbody>';

    if (empty($peserta)) {
        echo '<tr><td colspan="6" style="text-align:center; padding:20px;">Belum ada peserta yang diverifikasi panitia.</td></tr>';
    } else {
        $no = 1;
        foreach ($peserta as $p) {
            $kategori = get_post_meta($p->ID, 'kategori', true);
            $link_video = get_post_meta($p->ID, 'link_video', true);
            
            $nilai_saya_json = get_post_meta($p->ID, 'skor_juri_' . $current_juri_id, true);
            $nilai_saya = $nilai_saya_json ? json_decode($nilai_saya_json, true) : null;
            
            $semua_meta = get_post_meta($p->ID);
            $total_akumulasi = 0;
            $jumlah_juri_menilai = 0;
            
            foreach ($semua_meta as $key => $val) {
                if (strpos($key, 'skor_juri_') === 0) {
                    $data_juri = json_decode($val[0], true);
                    if (isset($data_juri['total'])) {
                        $total_akumulasi += floatval($data_juri['total']);
                        $jumlah_juri_menilai++;
                    }
                }
            }
            $rata_rata_akhir = ($jumlah_juri_menilai > 0) ? round($total_akumulasi / $jumlah_juri_menilai, 2) : 0;

            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td><strong>' . esc_html($p->post_title) . '</strong></td>';
            echo '<td style="text-align:center;">' . esc_html($kategori) . '</td>';
            
            echo '<td style="text-align:center;">';
            if ($link_video) {
                echo '<a href="' . esc_url($link_video) . '" target="_blank" class="button button-primary">Simak Video</a>';
            } else {
                echo '-';
            }
            echo '</td>';

            echo '<td style="text-align:center;">';
            if ($nilai_saya) {
                echo '<strong style="color:#047857; font-size:16px;">' . $nilai_saya['total'] . '</strong><br>';
                if(!empty($nilai_saya['catatan'])) {
                    echo '<small style="color:#64748b; font-style:italic;" title="'.esc_attr($nilai_saya['catatan']).'">"'.wp_trim_words($nilai_saya['catatan'], 5, '...').'"</small>';
                }
            } else {
                echo '<span style="color:#ef4444; font-weight:600;">Belum Dinilai</span>';
            }
            echo '</td>';

            echo '<td style="text-align:center; background:#f0fdf4;">';
            if ($rata_rata_akhir > 0) {
                echo '<strong style="font-size:16px; color:#1e293b;">' . $rata_rata_akhir . '</strong><br>';
                echo '<small>('.$jumlah_juri_menilai.' Juri)</small>';
            } else {
                echo '-';
            }
            echo '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table></div>';
    
    // Info pengalihan ke Frontend untuk menilai
    echo '<div style="margin-top:20px; padding:15px; background:#e0f2fe; border-left:4px solid #0284c7;">';
    echo '<p style="margin:0; font-weight:bold; color:#0369a1;">💡 Info Panitia: Untuk melakukan pengisian dan modifikasi nilai, silakan gunakan <b>Halaman Portal Juri (Front-End)</b> yang sudah disediakan di website.</p>';
    echo '</div>';
}

/* * ==========================================================
 * 7. FUNGSI API (AJAX) UNTUK PORTAL JURI FRONT-END
 * ==========================================================
 */
add_action('wp_ajax_ksk_get_data_juri_frontend', 'ksk_get_data_juri_frontend');
add_action('wp_ajax_nopriv_ksk_get_data_juri_frontend', 'ksk_get_data_juri_frontend'); 
function ksk_get_data_juri_frontend() {
    
    // A. Default Cek Login WordPress
    if (!is_user_logged_in()) { 
        wp_send_json_error('Sistem mendeteksi Anda belum login. Silakan Log In terlebih dahulu.'); 
    }

    $user = wp_get_current_user();
    $user_roles = (array) $user->roles;
    
    // B. Default Cek Role Sederhana (Harus ustadz atau administrator)
    if (!in_array('ustadz', $user_roles) && !in_array('administrator', $user_roles)) {
        wp_send_json_error('Akses ditolak. Halaman ini khusus untuk Juri Lomba.');
    }

    $current_juri_id = $user->ID;
    
    $peserta = get_posts(array(
        'post_type'      => 'pendaftar_adzan',
        'post_status'    => 'publish', 
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC'
    ));
    
    $data_peserta = array();

    foreach ($peserta as $p) {
        $kategori = get_post_meta($p->ID, 'kategori', true); 
        $link_video = get_post_meta($p->ID, 'link_video', true);
        
        $nilai_saya_json = get_post_meta($p->ID, 'skor_juri_' . $current_juri_id, true);
        $nilai_saya = $nilai_saya_json ? json_decode($nilai_saya_json, true) : null;
        
        $total_akumulasi = 0; 
        $jumlah_juri_menilai = 0;
        
        foreach (get_post_meta($p->ID) as $key => $val) {
            if (strpos($key, 'skor_juri_') === 0) { 
                $data_juri = json_decode($val[0], true); 
                if (isset($data_juri['total'])) { 
                    $total_akumulasi += floatval($data_juri['total']); 
                    $jumlah_juri_menilai++; 
                } 
            }
        }
        $rata_rata_akhir = ($jumlah_juri_menilai > 0) ? round($total_akumulasi / $jumlah_juri_menilai, 2) : 0;

        $data_peserta[] = array(
            'id' => $p->ID, 
            'nama' => $p->post_title, 
            'kategori' => $kategori, 
            'link_video' => $link_video, 
            'nilai_saya' => $nilai_saya, 
            'rata_rata' => $rata_rata_akhir, 
            'jumlah_juri' => $jumlah_juri_menilai
        );
    }
    
    wp_send_json_success($data_peserta);
}

add_action('wp_ajax_ksk_simpan_nilai_juri', 'ksk_ajax_simpan_nilai_juri');
function ksk_ajax_simpan_nilai_juri() {
    if (!is_user_logged_in()) { 
        wp_send_json_error('Sesi terputus. Silakan login kembali.'); 
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $juri_id = get_current_user_id();

    if (!$post_id) { 
        wp_send_json_error('Data peserta tidak valid.'); 
    }
    
    $data_nilai = array(
        'makhraj' => floatval($_POST['makhraj']), 
        'vokal'   => floatval($_POST['vokal']), 
        'adab'    => floatval($_POST['adab']), 
        'total'   => floatval($_POST['total']), 
        'catatan' => sanitize_textarea_field($_POST['catatan'])
    );
    
    update_post_meta($post_id, 'skor_juri_' . $juri_id, wp_json_encode($data_nilai));
    
    wp_send_json_success('Tersimpan.');
}