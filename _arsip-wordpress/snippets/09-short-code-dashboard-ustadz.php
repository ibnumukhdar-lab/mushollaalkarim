<?php
// SNIPPET WordPress #9: Short Code Dashboard Ustadz
// scope: global | status: nonaktif | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------


// ====================================================================
// 1. MESIN UTAMA (Logika & Form yang sama persis untuk Admin & Shortcode)
// ====================================================================
function render_simta_sistem_nilai() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'simta_perkembangan';
    $current_url = remove_query_arg(['action', 'id', '_wpnonce']);
    $current_user = wp_get_current_user();

    // A. PROSES HAPUS
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        if (wp_verify_nonce($_REQUEST['_wpnonce'], 'delete_nilai_' . $_GET['id'])) {
            $wpdb->delete($table_name, array('id' => intval($_GET['id'])));
            echo '<div class="notice notice-success" style="padding:10px; background:#d1fae5; color:#065f46; margin-bottom:15px;">Data berhasil dihapus.</div>';
        }
    }

    // B. PROSES SIMPAN/UPDATE
    if (isset($_POST['simta_submit_nilai']) && check_admin_referer('simta_save_nilai_action', 'simta_nilai_nonce')) {
        $santri_id = intval($_POST['santri_id']);
        $kategori  = sanitize_text_field($_POST['kategori']);
        
        // Membangun detail_materi sesuai kategori
        $detail = '';
        if ($kategori == "Tilawah Al-Qur'an") $detail = 'Juz ' . $_POST['tilawah_juz'] . ' - Hal ' . $_POST['tilawah_halaman'];
        elseif ($kategori == 'Latihan Iqro/Tilawati') $detail = 'Jilid ' . $_POST['iqro_jilid'] . ' - Hal ' . $_POST['iqro_halaman'];
        elseif ($kategori == "Hafalan Al-Qur'an") $detail = 'Juz ' . $_POST['hafalan_juz'] . ' | QS. ' . $_POST['hafalan_surah'] . ' | Ayat: ' . $_POST['hafalan_ayat'];

        $data = [
            'santri_id' => $santri_id, 'kategori' => $kategori, 'detail_materi' => $detail,
            'nilai_1' => intval($_POST['nilai_1']), 'nilai_2' => intval($_POST['nilai_2']), 'nilai_3' => intval($_POST['nilai_3']),
            'catatan_ustadz' => sanitize_textarea_field($_POST['catatan_ustadz']),
            'ustadz_nama' => $current_user->display_name, 'tanggal' => current_time('mysql')
        ];

        if (!empty($_POST['edit_id'])) {
            $wpdb->update($table_name, $data, ['id' => intval($_POST['edit_id'])]);
            echo '<script>window.location.href="'.$current_url.'";</script>';
        } else {
            $wpdb->insert($table_name, $data);
            echo '<div class="notice notice-success" style="padding:10px; background:#d1fae5; color:#065f46; margin-bottom:15px;">Data tersimpan.</div>';
        }
    }

    // C. AMBIL DATA EDIT
    $edit_data = null;
    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
        $edit_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['id'])));
    }

    // TAMPILAN (CSS & HTML)
    ?>
    <style>
        .simta-wrap { font-family: sans-serif; background: #fff; padding: 20px; border-radius: 16px; border: 1px solid #e5e7eb; }
        .simta-input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; margin-bottom: 10px; }
        .simta-btn { background: #10b981; color: #fff; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; }
        .simta-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .simta-table th, .simta-table td { padding: 12px; border: 1px solid #f3f4f6; text-align: left; }
    </style>
    
    <div class="simta-wrap">
        <h3><?php echo $edit_data ? 'Edit Penilaian' : 'Input Nilai Santri'; ?></h3>
        <form method="post" action="">
            <?php wp_nonce_field('simta_save_nilai_action', 'simta_nilai_nonce'); ?>
            <?php if($edit_data) echo '<input type="hidden" name="edit_id" value="'.$edit_data->id.'">'; ?>
            
            <select name="santri_id" class="simta-input" required>
                <option value="">-- Pilih Santri --</option>
                <?php foreach(get_posts(['post_type'=>'santri', 'numberposts'=>-1]) as $s) echo "<option value='{$s->ID}' ".selected($edit_data?$edit_data->santri_id:'', $s->ID, false).">{$s->post_title}</option>"; ?>
            </select>
            
            <select name="kategori" id="simta_kategori" class="simta-input" required>
                <option value="">-- Pilih Materi --</option>
                <option value="Tilawah Al-Qur'an" <?php selected($edit_data?$edit_data->kategori:'', "Tilawah Al-Qur'an"); ?>>Tilawah Al-Qur'an</option>
                <option value="Latihan Iqro/Tilawati" <?php selected($edit_data?$edit_data->kategori:'', "Latihan Iqro/Tilawati"); ?>>Latihan Iqro/Tilawati</option>
                <option value="Hafalan Al-Qur'an" <?php selected($edit_data?$edit_data->kategori:'', "Hafalan Al-Qur'an"); ?>>Hafalan Al-Qur'an</option>
            </select>

            <!-- Placeholder untuk detail dinamis (Hanya contoh ringkas, kembangkan sesuai kebutuhan form sebelumnya) -->
            <input type="text" name="detail_materi_full" class="simta-input" placeholder="Detail Materi (Contoh: Juz 1)" value="<?php echo $edit_data ? esc_attr($edit_data->detail_materi) : ''; ?>">
            
            <div style="display:flex; gap:10px;">
                <input type="number" name="nilai_1" class="simta-input" placeholder="N1" value="<?php echo $edit_data ? $edit_data->nilai_1 : ''; ?>">
                <input type="number" name="nilai_2" class="simta-input" placeholder="N2" value="<?php echo $edit_data ? $edit_data->nilai_2 : ''; ?>">
                <input type="number" name="nilai_3" class="simta-input" placeholder="N3" value="<?php echo $edit_data ? $edit_data->nilai_3 : ''; ?>">
            </div>
            
            <textarea name="catatan_ustadz" class="simta-input" placeholder="Catatan"><?php echo $edit_data ? esc_textarea($edit_data->catatan_ustadz) : ''; ?></textarea>
            
            <button type="submit" name="simta_submit_nilai" class="simta-btn">Simpan</button>
            <?php if($edit_data) echo '<a href="'.$current_url.'">Batal</a>'; ?>
        </form>

        <table class="simta-table">
            <thead><tr style="background:#f9fafb;"><th>Tanggal</th><th>Santri</th><th>Materi</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php 
            foreach ($wpdb->get_results("SELECT * FROM $table_name ORDER BY tanggal DESC LIMIT 10") as $row) {
                $del = wp_nonce_url($current_url.'&action=delete&id='.$row->id, 'delete_nilai_'.$row->id);
                echo "<tr>
                    <td>".date('d M', strtotime($row->tanggal))."</td>
                    <td>".get_the_title($row->santri_id)."</td>
                    <td>{$row->kategori}<br><small>{$row->detail_materi}</small></td>
                    <td><a href='".$current_url."&action=edit&id={$row->id}'>Edit</a> | <a href='{$del}' onclick='return confirm(\"Hapus?\")'>Hapus</a></td>
                </tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ====================================================================
// 2. REGISTRASI MENU ADMIN
// ====================================================================
add_action('admin_menu', function(){
    add_menu_page('Perkembangan Santri', 'Perkembangan', 'edit_posts', 'simta-perkembangan', 'render_simta_sistem_nilai', 'dashicons-chart-line', 26);
});

// ====================================================================
// 3. REGISTRASI SHORTCODE
// ====================================================================
add_shortcode('kelola_tasmi_muhafizh', function(){
    if (!is_user_logged_in()) return "Silakan login.";
    ob_start();
    render_simta_sistem_nilai();
    return ob_get_clean();
});