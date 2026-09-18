<?php
// SNIPPET WordPress #17: WA Sender Donatur (Item 3)
// scope: global | status: nonaktif | deskripsi: 
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

// Fungsi untuk memformat nomor HP jadi standar 62
function wa_format_nomor_hp($nomor) {
    // Hapus semua karakter selain angka
    $nomor = preg_replace('/[^0-9]/', '', $nomor); 
    // Ganti awalan 0 menjadi 62
    if (substr($nomor, 0, 1) == '0') {
        $nomor = '62' . substr($nomor, 1);
    }
    return $nomor;
}

// Menggantikan fungsi wa_donatur_data_callback sebelumnya
function wa_donatur_data_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wa_donatur';

    // --- PROSES SIMPAN MANUAL ---
    if (isset($_POST['submit_manual']) && check_admin_referer('simpan_donatur_manual')) {
        $nama = sanitize_text_field($_POST['nama_donatur']);
        $nomor = sanitize_text_field($_POST['nomor_wa']);
        $nomor_format = wa_format_nomor_hp($nomor);
        
        if (!empty($nama) && !empty($nomor_format)) {
            $wpdb->insert($table_name, array(
                'nama' => $nama,
                'nomor_wa' => $nomor_format,
                'is_subscribed' => 1
            ));
            echo '<div class="notice notice-success is-dismissible"><p>Data donatur berhasil ditambahkan!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Nama dan Nomor WA wajib diisi.</p></div>';
        }
    }

    // --- PROSES IMPORT CSV ---
    if (isset($_POST['submit_csv']) && check_admin_referer('simpan_donatur_csv')) {
        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($file, "r")) !== FALSE) {
                $row = 0;
                $success_count = 0;
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Melewati baris pertama (Header: Nama, Nomor WA)
                    if ($row > 0) { 
                        $nama = sanitize_text_field($data[0]);
                        $nomor = isset($data[1]) ? wa_format_nomor_hp($data[1]) : '';
                        
                        if (!empty($nama) && !empty($nomor)) {
                            $wpdb->insert($table_name, array(
                                'nama' => $nama,
                                'nomor_wa' => $nomor,
                                'is_subscribed' => 1
                            ));
                            $success_count++;
                        }
                    }
                    $row++;
                }
                fclose($handle);
                echo '<div class="notice notice-success is-dismissible"><p>'.$success_count.' data berhasil diimpor dari CSV!</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Silakan pilih file CSV terlebih dahulu.</p></div>';
        }
    }

    // --- TAMPILAN HALAMAN (UI) ---
    echo '<div class="wrap">';
    echo '<h1>Data Donatur & Kontak</h1>';
    
    echo '<div style="display:flex; gap: 20px; margin-top: 20px;">';
    
    // Form Input Manual
    echo '<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">';
    echo '<h3>Tambah Manual</h3>';
    echo '<form method="post" action="">';
    wp_nonce_field('simpan_donatur_manual');
    echo '<p><label>Nama Donatur</label><br><input type="text" name="nama_donatur" required style="width:100%;"></p>';
    echo '<p><label>Nomor WhatsApp (Cth: 08123456789)</label><br><input type="text" name="nomor_wa" required style="width:100%;"></p>';
    echo '<p><input type="submit" name="submit_manual" class="button button-primary" value="Simpan Data"></p>';
    echo '</form>';
    echo '</div>';

    // Form Import CSV
    echo '<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">';
    echo '<h3>Import via CSV</h3>';
    echo '<p>Pastikan format file CSV memiliki 2 kolom (Kolom 1: <b>Nama</b>, Kolom 2: <b>Nomor WA</b>). Baris pertama akan dibaca sebagai judul kolom (Header) dan tidak akan diimpor.</p>';
    echo '<form method="post" action="" enctype="multipart/form-data">';
    wp_nonce_field('simpan_donatur_csv');
    echo '<p><input type="file" name="csv_file" accept=".csv" required></p>';
    echo '<p><input type="submit" name="submit_csv" class="button button-secondary" value="Import File CSV"></p>';
    echo '</form>';
    echo '</div>';
    
    echo '</div>'; // End Flex Container

    // --- TABEL DATA DONATUR ---
    echo '<h2 style="margin-top:40px;">Daftar Kontak Tersimpan</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Nama Donatur</th><th>Nomor WA</th><th>Status Broadcast</th><th>Tanggal Ditambahkan</th></tr></thead>';
    echo '<tbody>';
    
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 50");
    if ($results) {
        foreach ($results as $row) {
            $status = $row->is_subscribed ? '<span style="color:green; font-weight:bold;">Aktif</span>' : '<span style="color:red;">Opt-Out</span>';
            echo '<tr>';
            echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html($row->nama) . '</td>';
            echo '<td>' . esc_html($row->nomor_wa) . '</td>';
            echo '<td>' . $status . '</td>';
            echo '<td>' . esc_html($row->tanggal_dibuat) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5">Belum ada data donatur.</td></tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>'; // End Wrap
}
