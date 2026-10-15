<?php
// Memanggil file konfigurasi sesi agar manajemen sesi web berjalan aman.
require_once(__DIR__ . '/inc/session_config.php');

// Memulai sesi PHP jika belum aktif.
session_start();

// Jika pengguna sudah dalam keadaan login (role tidak kosong),
// alihkan (redirect) langsung ke halaman utama (index.php) agar tidak perlu login ulang.
if (!empty($_SESSION['role'])) {
    header('Location: index.php');
    exit();
}

// Mengambil pesan kesalahan dari sesi jika ada (misal: gagal login), lalu hapus dari sesi setelah dibaca.
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <!-- Pengaturan karakter standar (UTF-8) dan skala tampilan agar responsif di berbagai perangkat -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistem Buku Tamu</title>

    <!-- Memuat file CSS dari kerangka AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <!-- Memuat pustaka ikon Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Memuat jenis huruf (typography) dari Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap"
        rel="stylesheet">
    <!-- Memuat file CSS kustom buatan sendiri -->
    <link rel="stylesheet" href="asset/css/style.css">
</head>

<body>

    <div class="page">

        <!-- Bagian atas (Topbar) halaman login -->
        <header class="topbar">
            <div class="identity">
                <div class="identity-mark">H</div>

                <div>
                    <div class="identity-name">Hotel</div>
                    <div class="identity-caption">Sistem informasi buku tamu</div>
                </div>
            </div>

            <div class="topbar-note">
                Portal internal · Front office
            </div>
        </header>

        <!-- Bagian utama halaman login -->
        <main class="main">
            <div class="login-layout">

                <!-- Panel bagian kiri (Berisi informasi sambutan sistem) -->
                <section class="intro">

                    <div class="intro-top">
                        <div class="section-label">
                            Front office
                        </div>
                    </div>

                    <div class="intro-copy">
                        <h1>
                            Sambut dengan baik.<br>
                            <span>Catat dengan tertib.</span>
                        </h1>

                        <p class="intro-description">
                            Satu ruang untuk mengelola kunjungan,
                            menjaga keteraturan data, dan mendukung
                            pelayanan yang lebih baik kepada setiap tamu.
                        </p>
                    </div>

                    <div class="intro-bottom">
                        <span class="status-dot"></span>
                        <span>Sistem pencatatan kunjungan tamu</span>
                    </div>

                </section>

                <!-- Panel bagian kanan (Berisi formulir masuk / login) -->
                <section class="form-panel">

                    <div class="form-index">
                        AKSES PENGGUNA / 01
                    </div>

                    <h2>Selamat datang.</h2>

                    <p class="form-subtitle">
                        Masuk menggunakan akun yang telah terdaftar
                        untuk melanjutkan ke sistem.
                    </p>

                    <!-- Menampilkan kotak pesan peringatan jika login gagal atau ada pesan dari sistem -->
                    <?php if (!empty($message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php endif; ?>

                    <!-- Formulir login yang mengirim data menggunakan metode POST ke file login_act.php -->
                    <form action="login_act.php" method="POST">

                        <!-- Input untuk alamat email pengguna -->
                        <div class="form-group">
                            <label for="email">ALAMAT EMAIL</label>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="far fa-envelope"></i>
                                    </span>
                                </div>

                                <input type="email" name="email" id="email" class="form-control"
                                    placeholder="nama@email.com" autocomplete="username" required autofocus>
                            </div>
                        </div>

                        <!-- Input untuk kata sandi (password) pengguna -->
                        <div class="form-group">
                            <label for="password">PASSWORD</label>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>

                                <input type="password" name="password" id="password" class="form-control"
                                    placeholder="Masukkan password" autocomplete="current-password" required>
                            </div>
                        </div>

                        <!-- Tombol untuk mengirimkan data formulir login -->
                        <button type="submit" name="submit" class="btn btn-login">
                            MASUK KE SISTEM
                            <i class="fas fa-arrow-right"></i>
                        </button>

                    </form>

                    <div class="form-divider"></div>

                    <!-- Keterangan kecil di bawah form -->
                    <div class="form-footnote">
                        <i class="fas fa-info-circle"></i>
                        <span>
                            Gunakan akun resmi yang telah diberikan
                            oleh administrator sistem.
                        </span>
                    </div>

                </section>

            </div>
        </main>

        <!-- Bagian bawah (Footer) halaman login -->
        <footer class="page-footer">
            <span>&copy; <?= date('Y') ?> · Sistem Informasi Buku Tamu</span>
            <span class="footer-right">Administrasi &amp; Pelayanan</span>
        </footer>

    </div>

    <!-- Memuat skrip JavaScript (jQuery, Bootstrap Bundle, dan AdminLTE) -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

</body>

</html>