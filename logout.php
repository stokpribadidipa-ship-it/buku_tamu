<?php
// Memuat pengaturan cookie sesi sebelum sesi dimulai.
require_once(__DIR__ . '/inc/session_config.php');
// Memulai sesi hanya jika belum aktif agar data sesi dapat dihapus.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mengosongkan seluruh data pengguna yang tersimpan pada sesi saat ini.
$_SESSION = [];

// Jika sesi menggunakan cookie, meminta browser menghapus cookie sesi yang sedang dipakai.
if (ini_get('session.use_cookies')) {
    // Mengambil pengaturan cookie yang sama agar cookie yang dihapus cocok dengan cookie aslinya.
    $params = session_get_cookie_params();

    // Mengirim cookie dengan masa berlaku di masa lalu agar browser menghapusnya.
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Menghapus data sesi di server setelah data di dalamnya dikosongkan.
session_destroy();

// Mengarahkan pengguna kembali ke halaman login, lalu menghentikan skrip.
header('Location: login.php');
exit;
?>