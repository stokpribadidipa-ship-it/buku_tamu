<?php
// Mengatur zona waktu default aplikasi menjadi waktu Jakarta (WIB).
date_default_timezone_set('Asia/Jakarta');

// Memanggil file konfigurasi sesi agar manajemen sesi web berjalan aman.
require_once(__DIR__ . '/session_config.php');

// Memulai sesi PHP jika sesi tersebut belum aktif.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Menyiapkan variabel dasar untuk direktori file ($base) dan judul halaman ($page_title) jika belum diatur sebelumnya.
$base = $base ?? '';
$page_title = $page_title ?? 'Dashboard';

// Mengambil peran pengguna (role) dan mengubahnya menjadi huruf kecil, serta menyiapkan nama panggilan pengguna.
$role = strtolower($_SESSION['role'] ?? '');
$nama = $_SESSION['nama'] ?? 'Pengguna';

// Mendapatkan nama file yang sedang diakses saat ini melalui URL.
$current_page = basename($_SERVER['PHP_SELF'] ?? '');

// Membuat fungsi untuk menandai menu mana yang sedang aktif (menyala/berwarna beda) di sidebar.
if (!function_exists('menuAktif')) {
    function menuAktif($halaman, $current_page)
    {
        // Jika nama file sama dengan halaman yang sedang dibuka, berikan nilai 'active', jika tidak kosongkan.
        return $halaman === $current_page ? 'active' : '';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <!-- Pengaturan jenis karakter standar web (UTF-8) dan skala tampilan agar responsif di HP/laptop -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Menampilkan judul halaman secara dinamis di tab browser -->
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?>Hotel
    </title>

    <!-- Memuat file lembar gaya CSS Bootstrap, Font Google, FontAwesome, dan ikon Bootstrap -->
    <link rel="stylesheet" href="<?= $base ?>asset/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Memuat file CSS tambahan milik sistem -->
    <link rel="stylesheet" href="<?= $base ?>asset/css/style.css">
</head>

<body>

    <!-- BAGIAN KEPALA HALAMAN (HEADER / NAVBAR ATAS) -->
    <header class="main-navbar">

        <div class="d-flex align-items-center gap-3">
            <!-- Tombol untuk membuka atau menutup sidebar di tampilan layar kecil (HP) -->
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Buka menu" aria-expanded="false"
                aria-controls="sidebar">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Logo dan judul aplikasi yang mengarah ke halaman utama -->
            <a href="<?= $base ?>index.php" class="brand-wrap">
                <div class="brand-mark">H</div>
                <div>
                    <span class="brand-name">Hotel</span>
                    <span class="brand-caption">Sistem Informasi Buku Tamu</span>
                </div>
            </a>
        </div>

        <!-- Menampilkan informasi profil singkat pengguna di pojok kanan jika sudah login -->
        <?php if (!empty($_SESSION['role'])): ?>
        <div class="header-right">
            <div class="header-user">
                <strong><?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></small>
            </div>
        </div>
        <?php endif; ?>

    </header>

    <div class="app-layout">

        <!-- BAGIAN MENU SAMPING (SIDEBAR) -->
        <aside class="sidebar" id="sidebar">

            <p class="sidebar-label">Menu Utama</p>

            <nav class="sidebar-menu">

                <!-- Pilihan menu khusus untuk pengguna dengan peran (role) Admin -->
                <?php if ($role === 'admin'): ?>

                <a href="<?= $base ?>admin/index.php" class="<?= menuAktif('index.php', $current_page) ?>">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>

                <a href="<?= $base ?>admin/list_tamu.php" class="<?= menuAktif('list_tamu.php', $current_page) ?>">
                    <i class="fas fa-address-book"></i>
                    <span>Data Tamu</span>
                </a>

                <a href="<?= $base ?>admin/list_staf.php" class="<?= menuAktif('list_staf.php', $current_page) ?>">
                    <i class="fas fa-user-tie"></i>
                    <span>Kelola Staf</span>
                </a>

                <a href="<?= $base ?>admin/list_user.php" class="<?= menuAktif('list_user.php', $current_page) ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Kelola Pengguna</span>
                </a>

                <!-- Pilihan menu khusus untuk pengguna dengan peran Resepsionis -->
                <?php elseif ($role === 'resepsionis'): ?>

                <a href="<?= $base ?>resepsionis/index.php" class="<?= menuAktif('index.php', $current_page) ?>">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>

                <a href="<?= $base ?>resepsionis/tamu_masuk.php"
                    class="<?= menuAktif('tamu_masuk.php', $current_page) ?>">
                    <i class="fas fa-user-plus"></i>
                    <span>Check-in Tamu</span>
                </a>

                <!-- Pilihan menu khusus untuk pengguna dengan peran Keamanan (Satpam) -->
                <?php elseif ($role === 'keamanan'): ?>

                <a href="<?= $base ?>keamanan/index.php" class="<?= menuAktif('index.php', $current_page) ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>

                <a href="<?= $base ?>keamanan/log_harian.php" class="<?= menuAktif('log_harian.php', $current_page) ?>">
                    <i class="fas fa-history"></i>
                    <span>Log Aktivitas</span>
                </a>

                <?php endif; ?>
                <!-- Catatan: Peran di sistem ini hanya admin, resepsionis, dan keamanan.
                     (Sesuai isi kolom ENUM role pada tabel tb_users di file buku_tamu.sql). -->

            </nav>

            <div class="sidebar-divider"></div>

            <p class="sidebar-label">Akun</p>

            <nav class="sidebar-menu">
                <!-- Tombol tautan untuk keluar dari sistem (logout) -->
                <a href="<?= $base ?>logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Keluar</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                HOTEL<br>
                SISTEM INFORMASI BUKU TAMU
            </div>

        </aside>

        <!-- KOTAK UTAMA UNTUK MENAMPILKAN KONTEN HALAMAN -->
        <main class="main-content" id="mainContent">

            <!-- Skrip JavaScript untuk mengatur interaksi buka-tutup menu samping (sidebar) pada perangkat seluler -->
            <script>
            (function() {
                // Mengambil tombol menu dan sidebar agar keduanya bisa dikendalikan oleh JavaScript.
                const toggle = document.getElementById('sidebarToggle');
                const sidebar = document.getElementById('sidebar');

                // Menjalankan pengaturan hanya jika kedua elemen tersedia di halaman.
                if (toggle && sidebar) {
                    // Ketika tombol toggle diklik, buka atau tutup sidebar secara bergantian
                    toggle.addEventListener('click', function() {
                        const isOpen = sidebar.classList.toggle('show');

                        // Menyamakan informasi status dan nama tombol untuk teknologi bantu seperti pembaca layar.
                        toggle.setAttribute('aria-expanded', String(isOpen));
                        toggle.setAttribute(
                            'aria-label',
                            isOpen ? 'Tutup menu' : 'Buka menu'
                        );
                    });

                    // Menutup sidebar otomatis jika pengguna mengklik area di luar sidebar pada layar kecil
                    document.addEventListener('click', function(event) {
                        if (
                            window.innerWidth <= 768 &&
                            sidebar.classList.contains('show') &&
                            !sidebar.contains(event.target) &&
                            !toggle.contains(event.target)
                        ) {
                            sidebar.classList.remove('show');
                            toggle.setAttribute('aria-expanded', 'false');
                            toggle.setAttribute('aria-label', 'Buka menu');
                        }
                    });
                }
            })();
            </script>