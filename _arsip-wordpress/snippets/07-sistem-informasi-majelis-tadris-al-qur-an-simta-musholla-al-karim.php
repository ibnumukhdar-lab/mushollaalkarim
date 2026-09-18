<?php
// SNIPPET WordPress #7: Sistem Informasi Majelis Tadris Al-Qur'an (SIMTA) - Musholla Al Karim
// scope: global | status: AKTIF | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

/**
 * Plugin Name: Sistem Informasi Majelis Tadris Al-Qur'an (SIMTA) - Musholla Al Karim
 * Description: Modul manajemen Ustadz & Santri dengan cetak kartu login, ganti password, kontrol aksi baris, serta auto-redirect halaman list data keseluruhan setelah publish.
 * Version: 2.1
 * Author: Admin Al Karim
 */

if (!defined('ABSPATH')) exit;

// ====================================================================
// 1. REGISTRASI CUSTOM POST TYPE & MENU SIDEBAR
// ====================================================================
function simta_register_custom_post_types() {
    $labels_ustadz = array(
        'name'               => 'Database Ustadz',
        'singular_name'      => 'Ustadz',
        'menu_name'          => 'Tadris: Ustadz',
        'add_new'            => 'Tambah Ustadz Baru',
        'add_new_item'       => 'Formulir Tambah Ustadz',
        'edit_item'          => 'Edit Data Ustadz',
        'all_items'          => 'Data Ustadz',
        'view_item'          => 'Lihat Ustadz',
        'search_items'       => 'Cari Ustadz',
        'not_found'          => 'Data Ustadz tidak ditemukan',
    );
    $args_ustadz = array(
        'labels'             => $labels_ustadz,
        'public'             => true,
        'has_archive'        => false,
        'menu_icon'          => 'dashicons-businessman',
        'supports'           => array('thumbnail'),
        'show_in_menu'       => true,
    );
    register_post_type('ustadz', $args_ustadz);

    $labels_santri = array(
        'name'               => 'Database Santri',
        'singular_name'      => 'Santri',
        'menu_name'          => 'Tadris: Santri',
        'add_new'            => 'Tambah Santri Baru',
        'add_new_item'       => 'Formulir Tambah Santri',
        'edit_item'          => 'Edit Data Santri',
        'all_items'          => 'Database Santri Keseluruhan',
        'view_item'          => 'Lihat Santri',
        'search_items'       => 'Cari Santri',
        'not_found'          => 'Data Santri tidak ditemukan',
    );
    $args_santri = array(
        'labels'             => $labels_santri,
        'public'             => true,
        'has_archive'        => false,
        'menu_icon'          => 'dashicons-welcome-learn-more',
        'supports'           => array('thumbnail'),
        'show_in_menu'       => true,
    );
    register_post_type('santri', $args_santri);
}
add_action('init', 'simta_register_custom_post_types');


// ====================================================================
// 2. METABOX FORM CALLBACKS
// ====================================================================
function simta_add_custom_metaboxes() {
    add_meta_box('meta_ustadz_fields', 'Informasi Lengkap Ustadz', 'simta_ustadz_metabox_callback', 'ustadz', 'normal', 'high');
    add_meta_box('meta_santri_fields', 'Informasi Lengkap Santri & Orang Tua', 'simta_santri_metabox_callback', 'santri', 'normal', 'high');
}
add_action('add_meta_boxes', 'simta_add_custom_metaboxes');

function simta_ustadz_metabox_callback($post) {
    wp_nonce_field('simta_save_ustadz_meta', 'simta_ustadz_nonce');
    echo '<table class="form-table simta-form-table">';
    echo '<tr><th><label>Nama Depan</label></th><td><input type="text" name="ustadz_first_name" value="'.esc_attr(get_post_meta($post->ID, '_ustadz_first_name', true)).'" class="regular-text" required /></td></tr>';
    echo '<tr><th><label>Nama Belakang</label></th><td><input type="text" name="ustadz_last_name" value="'.esc_attr(get_post_meta($post->ID, '_ustadz_last_name', true)).'" class="regular-text" required /></td></tr>';
    echo '<tr><th><label>Email (Akun Login)</label></th><td><input type="email" name="ustadz_email" value="'.esc_attr(get_post_meta($post->ID, '_ustadz_email', true)).'" class="regular-text" required /></td></tr>';
    echo '<tr><th><label>No. HP/WhatsApp</label></th><td><input type="text" name="ustadz_telepon" value="'.esc_attr(get_post_meta($post->ID, '_ustadz_telepon', true)).'" class="regular-text" /></td></tr>';
    echo '</table>';
}

function simta_santri_metabox_callback($post) {
    wp_nonce_field('simta_save_santri_meta', 'simta_santri_nonce');
    
    // Tarik mode ortu yang tersimpan, jika kosong jadikan 'baru' sebagai default
    $ortu_mode = get_post_meta($post->ID, '_ortu_mode', true);
    if(empty($ortu_mode)) $ortu_mode = 'baru';

    // Tarik data seluruh akun orang tua dari WP Users
    $ortu_users = get_users(array('role' => 'um_ortu'));

    echo '<table class="form-table simta-form-table">';
    echo '<tr><th colspan="2" style="background:#f0f0f0; padding:10px;"><b>DATA DIRI SANTRI</b></th></tr>';
    echo '<tr><th><label>Nama Lengkap Santri</label></th><td><input type="text" name="santri_nama_lengkap" value="'.esc_attr(get_post_meta($post->ID, '_santri_nama_lengkap', true)).'" class="regular-text" required /></td></tr>';
    echo '<tr><th><label>Tempat Lahir</label></th><td><input type="text" name="santri_tempat_lahir" value="'.esc_attr(get_post_meta($post->ID, '_santri_tempat_lahir', true)).'" class="regular-text" /></td></tr>';
    echo '<tr><th><label>Tanggal Lahir</label></th><td><input type="date" name="santri_tanggal_lahir" value="'.esc_attr(get_post_meta($post->ID, '_santri_tanggal_lahir', true)).'" class="regular-text" /></td></tr>';
    
    echo '<tr><th colspan="2" style="background:#f0f0f0; padding:10px; margin-top:15px;"><b>DATA ORANG TUA (OTOMATIS AKUN LOGIN)</b></th></tr>';
    
    // Opsi Radio Button untuk memilih tipe pengisian Data Ortu
    echo '<tr><th><label>Tipe Data Orang Tua</label></th><td>';
    echo '<label><input type="radio" name="ortu_mode" value="baru" '.checked($ortu_mode, 'baru', false).' onchange="simtaToggleOrtu(this.value)" /> Data orang tua baru</label><br>';
    echo '<label style="margin-top:5px; display:inline-block;"><input type="radio" name="ortu_mode" value="lama" '.checked($ortu_mode, 'lama', false).' onchange="simtaToggleOrtu(this.value)" /> Orang Tua Sudah Terdaftar</label>';
    echo '</td></tr>';

    // Dropdown Orang Tua yang sudah terdaftar
    $existing_ortu_id = get_post_meta($post->ID, '_ortu_wp_user_id', true);
    echo '<tr id="simta_row_ortu_lama" style="display:'.($ortu_mode == 'lama' ? 'table-row' : 'none').';">';
    echo '<th><label>Pilih Nama Orang Tua</label></th><td>';
    echo '<select name="santri_existing_ortu_id" id="santri_existing_ortu_id" style="width:100%; max-width:450px; padding:4px;">';
    echo '<option value="">-- Silakan Pilih Orang Tua --</option>';
    if (!empty($ortu_users)) {
        foreach($ortu_users as $ou) {
            echo '<option value="'.esc_attr($ou->ID).'" '.selected($existing_ortu_id, $ou->ID, false).'>Bpk. '.esc_html($ou->first_name . ' ' . $ou->last_name . ' (' . $ou->user_email . ')').'</option>';
        }
    } else {
        echo '<option value="" disabled>Belum ada data orang tua di sistem.</option>';
    }
    echo '</select>';
    echo '<p class="description">Pilih orang tua yang sudah ada agar akun login digabung untuk melihat perkembangan seluruh anak.</p>';
    echo '</td></tr>';

    // Bungkus baris-baris input Data Ortu Baru menggunakan <tbody>
    echo '<tbody id="simta_row_ortu_baru" style="display:'.($ortu_mode == 'baru' ? 'table-row-group' : 'none').';">';
    echo '<tr><th><label>Nama Depan Ayah</label></th><td><input type="text" id="santri_nama_ayah" name="santri_nama_ayah" value="'.esc_attr(get_post_meta($post->ID, '_santri_nama_ayah', true)).'" class="regular-text" /></td></tr>';
    echo '<tr><th><label>Nama Belakang Ayah</label></th><td><input type="text" id="ortu_last_name" name="ortu_last_name" value="'.esc_attr(get_post_meta($post->ID, '_ortu_last_name', true)).'" class="regular-text" /></td></tr>';
    echo '<tr><th><label>Nama Ibu</label></th><td><input type="text" id="santri_nama_ibu" name="santri_nama_ibu" value="'.esc_attr(get_post_meta($post->ID, '_santri_nama_ibu', true)).'" class="regular-text" /></td></tr>';
    echo '<tr><th><label>Email Orang Tua</label></th><td><input type="email" id="ortu_email" name="ortu_email" value="'.esc_attr(get_post_meta($post->ID, '_ortu_email', true)).'" class="regular-text" /></td></tr>';
    echo '</tbody>';
    echo '</table>';

    // Script interaktif untuk menyembunyikan/menampilkan formulir dan menonaktifkan atribut 'required'
    ?>
    <script type="text/javascript">
        function simtaToggleOrtu(mode) {
            var rowLama = document.getElementById("simta_row_ortu_lama");
            var rowBaru = document.getElementById("simta_row_ortu_baru");
            var inputIdOrtu = document.getElementById("santri_existing_ortu_id");
            var inputAyah = document.getElementById("santri_nama_ayah");
            var inputAyahLast = document.getElementById("ortu_last_name");
            var inputEmail = document.getElementById("ortu_email");

            if(mode === "baru") {
                rowLama.style.display = "none";
                rowBaru.style.display = "table-row-group";
                // Wajib diisi
                inputAyah.required = true;
                inputAyahLast.required = true;
                inputEmail.required = true;
                inputIdOrtu.required = false;
            } else {
                rowLama.style.display = "table-row";
                rowBaru.style.display = "none";
                // Hapus wajib isi agar bisa di-save
                inputAyah.required = false;
                inputAyahLast.required = false;
                inputEmail.required = false;
                inputIdOrtu.required = true;
            }
        }
        
        // Panggil saat halaman pertama kali diload agar sesuai status
        document.addEventListener("DOMContentLoaded", function() {
            var currentMode = document.querySelector('input[name="ortu_mode"]:checked');
            if(currentMode) {
                simtaToggleOrtu(currentMode.value);
            }
        });
    </script>
    <?php
}


// ====================================================================
// 3. PROSES SAVE DATA & SYNC USER
// ====================================================================
function simta_save_ustadz_details($post_id) {
    if (!isset($_POST['simta_ustadz_nonce']) || !wp_verify_nonce($_POST['simta_ustadz_nonce'], 'simta_save_ustadz_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $first = sanitize_text_field($_POST['ustadz_first_name']);
    $last  = sanitize_text_field($_POST['ustadz_last_name']);
    $email = sanitize_email($_POST['ustadz_email']);
    
    update_post_meta($post_id, '_ustadz_first_name', $first);
    update_post_meta($post_id, '_ustadz_last_name', $last);
    update_post_meta($post_id, '_ustadz_email', $email);
    update_post_meta($post_id, '_ustadz_telepon', sanitize_text_field($_POST['ustadz_telepon']));

    $full_name = trim("$first $last");
    if (!empty($full_name)) {
        remove_action('save_post_ustadz', 'simta_save_ustadz_details');
        wp_update_post(array('ID' => $post_id, 'post_title' => $full_name));
        add_action('save_post_ustadz', 'simta_save_ustadz_details');
    }

    if (!empty($email) && !email_exists($email)) {
        $username = strtolower(str_replace(' ', '', $first)) . rand(10, 99);
        $user_id = wp_create_user($username, 'alkarim123', $email);
        if (!is_wp_error($user_id)) {
            wp_update_user(array('ID' => $user_id, 'first_name' => $first, 'last_name' => $last, 'role' => 'um_ustadz'));
            update_post_meta($post_id, '_ustadz_username', $username);
            update_post_meta($post_id, '_ustadz_wp_user_id', $user_id);
        }
    }
}
add_action('save_post_ustadz', 'simta_save_ustadz_details');

function simta_save_santri_details($post_id) {
    if (!isset($_POST['simta_santri_nonce']) || !wp_verify_nonce($_POST['simta_santri_nonce'], 'simta_save_santri_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $nama_santri = sanitize_text_field($_POST['santri_nama_lengkap']);
    $ortu_mode   = isset($_POST['ortu_mode']) ? sanitize_text_field($_POST['ortu_mode']) : 'baru';

    update_post_meta($post_id, '_santri_nama_lengkap', $nama_santri);
    update_post_meta($post_id, '_santri_tempat_lahir', sanitize_text_field($_POST['santri_tempat_lahir']));
    update_post_meta($post_id, '_santri_tanggal_lahir', sanitize_text_field($_POST['santri_tanggal_lahir']));
    update_post_meta($post_id, '_ortu_mode', $ortu_mode);

    // Jika membuat akun Orang Tua Baru
    if ($ortu_mode === 'baru') {
        $ayah      = sanitize_text_field($_POST['santri_nama_ayah']);
        $ortu_last = sanitize_text_field($_POST['ortu_last_name']);
        $ibu       = sanitize_text_field($_POST['santri_nama_ibu']);
        $email_ortu= sanitize_email($_POST['ortu_email']);

        update_post_meta($post_id, '_santri_nama_ayah', $ayah);
        update_post_meta($post_id, '_ortu_last_name', $ortu_last);
        update_post_meta($post_id, '_santri_nama_ibu', $ibu);
        update_post_meta($post_id, '_ortu_email', $email_ortu);

        if (!empty($email_ortu) && !email_exists($email_ortu)) {
            $username_ortu = strtolower(str_replace(' ', '', $ayah)) . rand(10, 99);
            $user_id = wp_create_user($username_ortu, 'alkarim123', $email_ortu);
            if (!is_wp_error($user_id)) {
                wp_update_user(array('ID' => $user_id, 'first_name' => $ayah, 'last_name' => $ortu_last, 'role' => 'um_ortu'));
                update_post_meta($post_id, '_ortu_username', $username_ortu);
                update_post_meta($post_id, '_ortu_wp_user_id', $user_id);
            }
        }
    } 
    // Jika memilih Orang Tua yang Sudah Terdaftar
    else {
        $existing_ortu_id = intval($_POST['santri_existing_ortu_id']);
        if ($existing_ortu_id) {
            $parent_user = get_userdata($existing_ortu_id);
            if ($parent_user) {
                // Wariskan data ayah dan email dari database wp_users
                $ayah       = $parent_user->first_name;
                $ortu_last  = $parent_user->last_name;
                $email_ortu = $parent_user->user_email;
                $username_ortu = $parent_user->user_login;

                // Cari manual Nama Ibu dari kakak/saudara santri ini
                global $wpdb;
                $ibu = $wpdb->get_var($wpdb->prepare(
                    "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_santri_nama_ibu' AND post_id IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ortu_wp_user_id' AND meta_value = %d) LIMIT 1",
                    $existing_ortu_id
                ));
                if(empty($ibu)) $ibu = ''; 

                update_post_meta($post_id, '_santri_nama_ayah', $ayah);
                update_post_meta($post_id, '_ortu_last_name', $ortu_last);
                update_post_meta($post_id, '_santri_nama_ibu', $ibu);
                update_post_meta($post_id, '_ortu_email', $email_ortu);
                update_post_meta($post_id, '_ortu_username', $username_ortu);
                update_post_meta($post_id, '_ortu_wp_user_id', $existing_ortu_id);
            }
        }
    }

    // Paksa update judul post agar sesuai dengan nama santri
    if (!empty($nama_santri)) {
        remove_action('save_post_santri', 'simta_save_santri_details');
        wp_update_post(array('ID' => $post_id, 'post_title' => $nama_santri));
        add_action('save_post_santri', 'simta_save_santri_details');
    }
}
add_action('save_post_santri', 'simta_save_santri_details');


// ====================================================================
// 4. PENYETELAN REDIRECT SETELAH PUBLISH / UPDATE DATA
// ====================================================================
function simta_redirect_after_publish($location, $post_id) {
    $post_type = get_post_type($post_id);
    
    // Periksa apakah post berasal dari modul kita, jika iya belokkan ke halaman list keseluruhan
    if ('ustadz' === $post_type) {
        return admin_url('edit.php?post_type=ustadz');
    } elseif ('santri' === $post_type) {
        return admin_url('edit.php?post_type=santri');
    }
    
    return $location;
}
add_filter('redirect_post_location', 'simta_redirect_after_publish', 10, 2);


// ====================================================================
// 5. MODIFIKASI STRUKTUR & DATA KOLOM TABEL ADMIN
// ====================================================================

// Tabel Ustadz
function simta_set_ustadz_columns($columns) {
    return array(
        'cb'        => '<input type="checkbox" />',
        'title'     => 'Nama Lengkap Ustadz',
        'username'  => 'Username',
        'email'     => 'Email Login',
        'telepon'   => 'No. HP/WA',
        'aksi'      => 'Kelola Akses & Data'
    );
}
function simta_custom_ustadz_column($column, $post_id) {
    $user_id  = get_post_meta($post_id, '_ustadz_wp_user_id', true);
    $username = get_post_meta($post_id, '_ustadz_username', true) ? get_post_meta($post_id, '_ustadz_username', true) : '-';
    
    if ($column == 'username') {
        echo '<strong>' . esc_html($username) . '</strong>';
    } elseif ($column == 'email') {
        echo esc_html(get_post_meta($post_id, '_ustadz_email', true));
    } elseif ($column == 'telepon') {
        echo esc_html(get_post_meta($post_id, '_ustadz_telepon', true));
    } elseif ($column == 'aksi') {
        $edit_link = get_edit_post_link($post_id);
        $delete_link = get_delete_post_link($post_id);
        
        echo '<div class="simta-action-buttons">';
        echo '<button class="button button-secondary button-small" onclick="simtaCetak(\''.esc_js($username).'\', \'Ustadz\')"><span class="dashicons dashicons-id"></span> Cetak</button> ';
        if ($user_id) {
            echo '<button class="button button-secondary button-small btn-pass" onclick="simtaGantiPassword('.intval($user_id).')"><span class="dashicons dashicons-lock"></span> Pass</button> ';
        }
        echo '<a href="'.esc_url($edit_link).'" class="button button-small btn-edit"><span class="dashicons dashicons-edit"></span></a> ';
        echo '<a href="'.esc_url($delete_link).'" class="button button-small btn-del" onclick="return confirm(\'Hapus data ustadz ini?\')"><span class="dashicons dashicons-trash"></span></a>';
        echo '</div>';
    }
}
add_filter('manage_ustadz_posts_columns', 'simta_set_ustadz_columns');
add_action('manage_ustadz_posts_custom_column', 'simta_custom_ustadz_column', 10, 2);

// Tabel Santri
function simta_set_santri_columns($columns) {
    return array(
        'cb'            => '<input type="checkbox" />',
        'title'         => 'Nama Santri',
        'ttl'           => 'TTL',
        'ortu'          => 'Orang Tua (Ayah / Ibu)',
        'username_ortu' => 'Username Ortu',
        'email_ortu'    => 'Email Ortu',
        'aksi_santri'   => 'Kelola Akses & Data'
    );
}
function simta_custom_santri_column($column, $post_id) {
    $user_id = get_post_meta($post_id, '_ortu_wp_user_id', true);
    $username_ortu = get_post_meta($post_id, '_ortu_username', true) ? get_post_meta($post_id, '_ortu_username', true) : '-';

    if ($column == 'ttl') {
        $tmp = get_post_meta($post_id, '_santri_tempat_lahir', true);
        $tgl = get_post_meta($post_id, '_santri_tanggal_lahir', true);
        echo esc_html(($tmp || $tgl) ? "$tmp, $tgl" : '-');
    } elseif ($column == 'ortu') {
        $ayah = get_post_meta($post_id, '_santri_nama_ayah', true);
        $ibu  = get_post_meta($post_id, '_santri_nama_ibu', true);
        echo esc_html("Bpk. $ayah / Ibu $ibu");
    } elseif ($column == 'username_ortu') {
        echo '<strong>' . esc_html($username_ortu) . '</strong>';
    } elseif ($column == 'email_ortu') {
        echo esc_html(get_post_meta($post_id, '_ortu_email', true));
    } elseif ($column == 'aksi_santri') {
        $edit_link = get_edit_post_link($post_id);
        $delete_link = get_delete_post_link($post_id);
        
        echo '<div class="simta-action-buttons">';
        echo '<button class="button button-secondary button-small" onclick="simtaCetak(\''.esc_js($username_ortu).'\', \'Orang Tua\')"><span class="dashicons dashicons-id"></span> Cetak</button> ';
        if ($user_id) {
            echo '<button class="button button-secondary button-small btn-pass" onclick="simtaGantiPassword('.intval($user_id).')"><span class="dashicons dashicons-lock"></span> Pass</button> ';
        }
        echo '<a href="'.esc_url($edit_link).'" class="button button-small btn-edit"><span class="dashicons dashicons-edit"></span></a> ';
        echo '<a href="'.esc_url($delete_link).'" class="button button-small btn-del" onclick="return confirm(\'Hapus data santri ini?\')"><span class="dashicons dashicons-trash"></span></a>';
        echo '</div>';
    }
}
add_filter('manage_santri_posts_columns', 'simta_set_santri_columns');
add_action('manage_santri_posts_custom_column', 'simta_custom_santri_column', 10, 2);


// ====================================================================
// 6. ENGINE HANDLER AJAX: MODAL & GANTI PASSWORD
// ====================================================================
function simta_admin_scripts_and_modals() {
    global $post_type;
    if ('ustadz' != $post_type && 'santri' != $post_type) return;
    ?>
    <div id="simtaModal" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); font-family:sans-serif;">
        <div style="background:#fff; margin:15% auto; padding:20px; border-radius:5px; width:320px; box-shadow:0 4px 10px rgba(0,0,0,0.3); position:relative;">
            <span onclick="document.getElementById('simtaModal').style.display='none'" style="position:absolute; right:12px; top:8px; font-size:20px; cursor:pointer; color:#999;">&times;</span>
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:8px;">Ganti Password</h3>
            <input type="hidden" id="simta_target_user_id" value="">
            <p><label style="font-weight:bold; display:block; margin-bottom:5px;">Password Baru:</label>
            <input type="text" id="simta_new_password" style="width:100%; padding:6px;" placeholder="Ketik password baru..."></p>
            <button class="button button-primary" onclick="simtaEksekusiGantiPassword()" style="width:100%; margin-top:10px;">Simpan Perubahan</button>
        </div>
    </div>

    <script type="text/javascript">
        function simtaCetak(username, tipe) {
            var jendelaCetak = window.open('', '_blank', 'width=500,height=400');
            jendelaCetak.document.write('<html><head><title>Kartu Akses Login</title>');
            jendelaCetak.document.write('<style>body{font-family:sans-serif; text-align:center; padding:20px;} .card{border:3px dashed #00a0d2; padding:20px; border-radius:10px; background:#f4f9fa;} h2{margin-top:0; color:#23282d;} .info{font-size:16px; margin:10px 0;} .footer{font-size:11px; margin-top:20px; color:#666;}</style>');
            jendelaCetak.document.write('</head><body>');
            jendelaCetak.document.write('<div class="card">');
            jendelaCetak.document.write('<h2>KARTU LOGIN ' + tipe.toUpperCase() + '</h2>');
            jendelaCetak.document.write('<p><strong>Musholla Al Karim (Majelis Tadris)</strong></p><hr>');
            jendelaCetak.document.write('<div class="info"><strong>Link Login:</strong> mushollaalkarim.web.id/login</div>');
            jendelaCetak.document.write('<div class="info"><strong>Username:</strong> ' + username + '</div>');
            jendelaCetak.document.write('<div class="info"><strong>Password Default:</strong> alkarim123</div>');
            jendelaCetak.document.write('<div class="footer">*Harap simpan kartu ini dengan aman atau segera ubah password Anda.</div>');
            jendelaCetak.document.write('</div>');
            jendelaCetak.document.write('</body></html>');
            jendelaCetak.document.close();
            jendelaCetak.print();
        }

        function simtaGantiPassword(userId) {
            document.getElementById('simta_target_user_id').value = userId;
            document.getElementById('simta_new_password').value = '';
            document.getElementById('simtaModal').style.display = 'block';
        }

        function simtaEksekusiGantiPassword() {
            var uid = document.getElementById('simta_target_user_id').value;
            var pass = document.getElementById('simta_new_password').value;
            if(!pass) { alert('Password tidak boleh kosong!'); return; }

            jQuery.post(ajaxurl, {
                action: 'simta_update_password_user',
                user_id: uid,
                new_pass: pass,
                nonce: '<?php echo wp_create_nonce("simta_pass_nonce"); ?>'
            }, function(res) {
                if(res.success) {
                    alert('Password berhasil diperbarui!');
                    document.getElementById('simtaModal').style.display = 'none';
                } else {
                    alert('Gagal memperbarui: ' + res.data);
                }
            });
        }
    </script>
    <?php
}
add_action('admin_footer', 'simta_admin_scripts_and_modals');

function simta_update_password_user_callback() {
    check_ajax_referer('simta_pass_nonce', 'nonce');
    if (!current_user_can('edit_users')) { wp_send_json_error('Akses ditolak.'); }

    $user_id  = intval($_POST['user_id']);
    $new_pass = sanitize_text_field($_POST['new_pass']);

    if ($user_id && !empty($new_pass)) {
        wp_set_password($new_pass, $user_id);
        wp_send_json_success();
    }
    wp_send_json_error('Data tidak valid.');
}
add_action('wp_ajax_simta_update_password_user', 'simta_update_password_user_callback');


// ====================================================================
// 7. PENYESUAIAN CSS ADMIN & STYLE TOMBOL AKSI
// ====================================================================
function simta_admin_styles() {
    global $post_type;
    if ('ustadz' == $post_type || 'santri' == $post_type) {
        echo '<style>
            #poststuff #titlewrap, #edit-slug-box { display: none !important; }
            .row-actions { display: none !important; } 
            .column-aksi, .column-aksi_santri { width: 190px !important; }
            .simta-action-buttons { display: flex; gap: 4px; align-items: center; }
            .simta-action-buttons .button-small { padding: 3px 6px !important; min-height: 26px !important; line-height: 1.4 !important; }
            .simta-action-buttons .btn-edit { background: #e5f5fa !important; border-color: #00a0d2 !important; color: #0073aa !important; }
            .simta-action-buttons .btn-edit:hover { background: #00a0d2 !important; color: #fff !important; }
            .simta-action-buttons .btn-del { background: #fdf2f2 !important; border-color: #d63638 !important; color: #d63638 !important; }
            .simta-action-buttons .btn-del:hover { background: #d63638 !important; color: #fff !important; }
            .simta-action-buttons .btn-pass { background: #f0f6f8 !important; border-color: #4f5d73 !important; color: #2c3338 !important; }
            .simta-action-buttons .btn-pass:hover { background: #4f5d73 !important; color: #fff !important; }
            .simta-form-table { width: 100%; border-collapse: collapse; }
            .simta-form-table th { width: 25%; text-align: left; padding: 12px; font-weight: 600; color: #222; }
            .simta-form-table td { padding: 12px; }
            .simta-form-table input[type="text"], .simta-form-table input[type="email"], .simta-form-table input[type="date"] {
                width: 100%; max-width: 450px; padding: 6px 10px; border-radius: 4px; border: 1px solid #8c8f94;
            }
        </style>';
    }
}
add_action('admin_head', 'simta_admin_styles');