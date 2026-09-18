<?php
// SNIPPET WordPress #2: Disable admin bar
// scope: front-end | status: tidak aktif | deskripsi: Turns off the WordPress admin bar for everyone except administrators.  This is a sample snippet. Feel free to use it, edit it, or remove it.
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

add_action( 'wp', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		show_admin_bar( false );
	}
} );