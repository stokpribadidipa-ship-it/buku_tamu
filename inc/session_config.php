<?php
// Mengatur keamanan cookie sesi (session). File ini WAJIB dipanggil SEBELUM perintah session_start()
// dijalankan di halaman mana pun karena PHP menolak pengaturan cookie jika sesi sudah terlanjur berjalan.
// Bersifat aman (idempotent), artinya aman jika dipanggil berkali-kali dalam satu proses pemuatan halaman.
if (session_status() === PHP_SESSION_NONE) {
    // Mengecek apakah website sedang berjalan menggunakan protokol HTTPS (koneksi aman).
    // Memeriksa variabel server HTTPS, port server 443, atau header pembawa protokol dari proxy.
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        ($_SERVER['SERVER_PORT'] ?? '') == 443 ||
        (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    );

    // Mengaktifkan mode ketat sesi untuk mencegah serangan penculikan sesi (session fixation).
    ini_set('session.use_strict_mode', '1');

    // Mengatur parameter keamanan untuk cookie sesi browser.
    session_set_cookie_params([
        'lifetime' => 0,            // Cookie sesi akan kedaluwarsa saat browser ditutup oleh pengguna.
        'path'     => '/',          // Cookie berlaku di seluruh direktori website ini.
        'domain'   => '',           // Menggunakan domain saat ini secara otomatis.
        'secure'   => $isHttps,     // Cookie hanya dikirim jika koneksi menggunakan HTTPS (aman dari penyadapan).
        'httponly' => true,         // Mencegah cookie diakses oleh JavaScript (perlindungan terhadap pencurian sesi via XSS).
        'samesite' => 'Lax',        // Pengamanan dasar untuk mencegah serangan pemalsuan permintaan lintas situs (CSRF).
    ]);
}
?>