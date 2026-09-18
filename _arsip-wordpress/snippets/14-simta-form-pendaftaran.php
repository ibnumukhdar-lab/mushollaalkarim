<?php
// SNIPPET WordPress #14: SIMTA FORM PENDAFTARAN
// scope: global | status: AKTIF | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

// ====================================================================
// 8. SHORTCODE FORM PENDAFTARAN SANTRI (FRONTEND)
// Shortcode: [simta_form_pendaftaran]
// ====================================================================
function simta_pendaftaran_santri_shortcode() {
    ob_start();

    // Proses data jika form disubmit
    $pesan_sukses = '';
    if (isset($_POST['simta_submit_pendaftaran'])) {
        // Validasi Keamanan (Nonce)
        if (isset($_POST['simta_pendaftaran_nonce']) && wp_verify_nonce($_POST['simta_pendaftaran_nonce'], 'proses_pendaftaran_simta')) {
            
            $nama_santri = sanitize_text_field($_POST['santri_nama']);
            $tempat      = sanitize_text_field($_POST['santri_tempat']);
            $tanggal     = sanitize_text_field($_POST['santri_tanggal']);
            
            $ayah        = sanitize_text_field($_POST['ortu_ayah']);
            $belakang    = sanitize_text_field($_POST['ortu_belakang']);
            $ibu         = sanitize_text_field($_POST['ortu_ibu']);
            $email       = sanitize_email($_POST['ortu_email']);
            $wa          = sanitize_text_field($_POST['ortu_wa']); 
            
            // Mengambil ID Orang Tua dari Dropdown jika dipilih
            $selected_ortu_id = isset($_POST['simta_pilih_ortu']) ? intval($_POST['simta_pilih_ortu']) : 0;

            // 1. Buat post Santri dengan status PENDING (Menunggu Peninjauan)
            $post_data = array(
                'post_title'   => $nama_santri,
                'post_type'    => 'santri',
                'post_status'  => 'pending', // KUNCI: Data masuk sebagai draf tertunda, bukan terbit.
            );
            $post_id = wp_insert_post($post_data);

            if (!is_wp_error($post_id)) {
                // 2. Simpan Meta Data agar tampil di form admin nantinya
                update_post_meta($post_id, '_santri_nama_lengkap', $nama_santri);
                update_post_meta($post_id, '_santri_tempat_lahir', $tempat);
                update_post_meta($post_id, '_santri_tanggal_lahir', $tanggal);
                
                // Logika Hubungkan Akun berdasarkan Pilihan Dropdown
                if ( $selected_ortu_id > 0 ) {
                    update_post_meta($post_id, '_ortu_mode', 'terdaftar'); 
                    update_post_meta($post_id, '_simta_parent_user_id', $selected_ortu_id); // HUBUNGKAN KE USER ID ORTU
                    
                    // Opsional: Jika memilih dari dropdown, ambil data asli dari akun tersebut agar sinkron
                    $user_info = get_userdata($selected_ortu_id);
                    if ($user_info) {
                        $ayah     = get_user_meta($selected_ortu_id, 'first_name', true) ?: $ayah;
                        $belakang = get_user_meta($selected_ortu_id, 'last_name', true) ?: $belakang;
                        $email    = $user_info->user_email ?: $email;
                        $ibu      = get_user_meta($selected_ortu_id, 'nama_ibu', true) ?: $ibu; // sesuaikan meta key UM Anda
                        $wa       = get_user_meta($selected_ortu_id, 'ortu_wa', true) ?: $wa;   // sesuaikan meta key UM Anda
                    }
                } else {
                    update_post_meta($post_id, '_ortu_mode', 'baru'); 
                }
                
                update_post_meta($post_id, '_santri_nama_ayah', $ayah);
                update_post_meta($post_id, '_ortu_last_name', $belakang);
                update_post_meta($post_id, '_santri_nama_ibu', $ibu);
                update_post_meta($post_id, '_ortu_email', $email);
                update_post_meta($post_id, '_ortu_wa', $wa); // Simpan WA

                $pesan_sukses = '<div class="simta-alert-success">Alhamdulillah, data Ananda <strong>' . esc_html($nama_santri) . '</strong> berhasil dikirim. Silakan tunggu konfirmasi dari Ustadz/Admin.</div>';
            }
        }
    }

    // Tampilkan pesan sukses jika ada
    echo $pesan_sukses;
    ?>

    <style>
        .simta-form-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #e6f4ea;
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.05);
            font-family: 'Inter', sans-serif;
        }
        .simta-form-group {
            margin-bottom: 20px;
        }
        .simta-form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }
        /* Update selector agar Style juga berlaku pada Dropdown/Select */
        .simta-form-group input, .simta-form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color 0.3s;
            background-color: #fff;
        }
        .simta-form-group input:focus, .simta-form-group select:focus {
            border-color: #059669;
            outline: none;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }
        .simta-form-title {
            font-size: 18px;
            color: #065f46;
            border-bottom: 2px dashed #a7f3d0;
            padding-bottom: 10px;
            margin-bottom: 20px;
            margin-top: 30px;
            font-weight: 700;
        }
        .simta-form-title:first-child { margin-top: 0; }
        .simta-btn-submit {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #fff;
            border: none;
            padding: 14px 20px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            transition: opacity 0.3s;
        }
        .simta-btn-submit:hover { opacity: 0.9; }
        .simta-alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            padding: 15px;
            border-left: 4px solid #10b981;
            border-radius: 4px;
            margin-bottom: 25px;
        }

        /* Responsive tambahan untuk input sejajar */
        @media (max-width: 480px) {
            .simta-flex-row {
                flex-direction: column;
                gap: 0 !important;
            }
        }
    </style>

    <?php if (empty($pesan_sukses)) : ?>
    <div class="simta-form-container">
        <form action="" method="post">
            <?php wp_nonce_field('proses_pendaftaran_simta', 'simta_pendaftaran_nonce'); ?>
            
            <div class="simta-form-title">Data Calon Santri</div>
            
            <div class="simta-form-group">
                <label>Nama Lengkap Anak *</label>
                <input type="text" name="santri_nama" placeholder="Contoh: Muhammad Al-Fatih" required>
            </div>
            <div class="simta-flex-row" style="display: flex; gap: 15px;">
                <div class="simta-form-group" style="flex: 1;">
                    <label>Tempat Lahir</label>
                    <input type="text" name="santri_tempat" placeholder="Contoh: Jakarta">
                </div>
                <div class="simta-form-group" style="flex: 1;">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="santri_tanggal">
                </div>
            </div>

            <div class="simta-form-title">Data Orang Tua / Wali</div>

            <div class="simta-form-group">
                <label>Pilih Akun Orang Tua Terdaftar (Hubungkan Otomatis)</label>
                <select name="simta_pilih_ortu" id="um_ortu">
                    <option value="">-- Lewati ini jika ingin membuat Data Baru (Isi Manual) --</option>
                    <?php
                    // Query mengambil semua user dengan role um_ortu
                    $daftar_ortu = get_users(array(
                        'role'    => 'um_ortu',
                        'orderby' => 'display_name',
                        'order'   => 'ASC'
                    ));

                    if ( !empty($daftar_ortu) ) {
                        foreach ( $daftar_ortu as $ortu ) {
                            echo '<option value="' . esc_attr($ortu->ID) . '">' . esc_html($ortu->display_name) . ' (' . esc_html($ortu->user_email) . ')</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="simta-flex-row" style="display: flex; gap: 15px;">
                <div class="simta-form-group" style="flex: 1;">
                    <label>Nama Depan Ayah *</label>
                    <input type="text" name="ortu_ayah" required>
                </div>
                <div class="simta-form-group" style="flex: 1;">
                    <label>Nama Belakang Ayah *</label>
                    <input type="text" name="ortu_belakang" required>
                </div>
            </div>

            <div class="simta-form-group">
                <label>Nama Lengkap Ibu *</label>
                <input type="text" name="ortu_ibu" required>
            </div>

            <div class="simta-flex-row" style="display: flex; gap: 15px;">
                <div class="simta-form-group" style="flex: 1;">
                    <label>Alamat Email Aktif *</label>
                    <input type="email" name="ortu_email" placeholder="Akses login akan dikirim ke sini" required>
                </div>
                <div class="simta-form-group" style="flex: 1;">
                    <label>Nomor WhatsApp *</label>
                    <input type="text" name="ortu_wa" placeholder="Contoh: 081234567890" required>
                </div>
            </div>

            <button type="submit" name="simta_submit_pendaftaran" class="simta-btn-submit">Kirim Formulir Pendaftaran</button>
        </form>
    </div>
    <?php endif; ?>

    <?php
    return ob_get_clean();
}
add_shortcode('simta_form_pendaftaran', 'simta_pendaftaran_santri_shortcode');