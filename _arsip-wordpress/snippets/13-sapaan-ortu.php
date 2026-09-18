<?php
// SNIPPET WordPress #13: SAPAAN ORTU
// scope: global | status: AKTIF | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

// ====================================================================
// SHORTCODE HERO SECTION / KOTAK SAPAAN ORANG TUA
// Shortcode: [simta_sapaan_ortu]
// ====================================================================
function simta_hero_section_shortcode($atts) {
    // Jika belum login, tidak perlu menampilkan hero section (atau bisa disesuaikan)
    if (!is_user_logged_in()) {
        return '';
    }

    // Ambil data user yang sedang login
    $current_user = wp_get_current_user();
    $nama_ortu = $current_user->display_name;

    ob_start();
    ?>
    <h2>Ahlan Wa Sahlan, Bapak/Ibu <?php echo esc_html($nama_ortu); ?>!</h2>
    <p>Berikut ini adalah laporan hasil belajar real-time Ananda di Musholla Al-Karim.</p>
    <?php
    return ob_get_clean();
}
add_shortcode('simta_sapaan_ortu', 'simta_hero_section_shortcode');