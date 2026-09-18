<?php
// SNIPPET WordPress #12: METABOX RELASI SANTRI DENGAN AKUN ORANG TUA (USER)
// scope: global | status: AKTIF | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

// ====================================================================
// 12. METABOX RELASI SANTRI DENGAN AKUN ORANG TUA (USER)
// ====================================================================
function simta_add_parent_metabox() {
    add_meta_box('simta_parent_meta', 'Akun Orang Tua', 'simta_parent_metabox_callback', 'santri', 'side', 'high');
}
add_action('add_meta_boxes', 'simta_add_parent_metabox');

function simta_parent_metabox_callback($post) {
    wp_nonce_field('simta_save_parent_data', 'simta_parent_meta_nonce');
    
    // Ambil ID orang tua yang sudah tersimpan sebelumnya
    $current_parent = get_post_meta($post->ID, '_simta_ortu_id', true);
    
    // Ambil daftar semua user WordPress (biasanya orang tua diberi role 'subscriber')
    $users = get_users(array('role__in' => array('subscriber', 'contributor', 'author', 'editor', 'administrator'))); 
    
    echo '<label for="simta_ortu_id" style="display:block; margin-bottom:5px;">Pilih Akun WP Orang Tua:</label>';
    echo '<select name="simta_ortu_id" id="simta_ortu_id" style="width:100%;">';
    echo '<option value="">-- Belum Dihubungkan --</option>';
    
    foreach ($users as $user) {
        $selected = ($current_parent == $user->ID) ? 'selected' : '';
        echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</option>';
    }
    
    echo '</select>';
    echo '<p class="description">Pilih akun pengguna (user) agar orang tua tersebut bisa melihat laporan anak ini di halaman depan.</p>';
}

// Simpan data relasi saat post santri disimpan/diupdate
function simta_save_parent_meta($post_id) {
    if (!isset($_POST['simta_parent_meta_nonce']) || !wp_verify_nonce($_POST['simta_parent_meta_nonce'], 'simta_save_parent_data')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['simta_ortu_id'])) {
        update_post_meta($post_id, '_simta_ortu_id', sanitize_text_field($_POST['simta_ortu_id']));
    }
}
add_action('save_post', 'simta_save_parent_meta');