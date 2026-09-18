<?php
// SNIPPET WordPress #1: Make upload filenames lowercase
// scope: global | status: tidak aktif | deskripsi: Makes sure that image and file uploads have lowercase filenames.  This is a sample snippet. Feel free to use it, edit it, or remove it.
// ---------------------------------------------------------------------
// ARSIP dari tabel wpvr_snippets (mushollaalkarim.web.id) — referensi migrasi ke Laravel.
// JANGAN dijalankan langsung: kode asli mengandalkan fungsi/konteks WordPress.
// ---------------------------------------------------------------------

add_filter( 'sanitize_file_name', 'mb_strtolower' );