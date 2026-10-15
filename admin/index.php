<?php
// Memuat pemeriksaan login dan memastikan hanya pengguna dengan peran admin yang dapat membuka halaman ini.
require_once('../inc/auth.php');
cek_role(['admin']);

// Memuat pengaturan koneksi dan membuat koneksi database untuk mengambil data dashboard.
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

// Statistik dashboard
// Menghitung jumlah tamu yang check-in pada tanggal hari ini.
$total_hari_ini = $conn->query("
    SELECT COUNT(*)
    FROM tb_tamu
    WHERE DATE(waktu_checkin) = CURDATE()
")->fetchColumn();

$total_minggu_ini = $conn->query("
    SELECT COUNT(*)
    FROM tb_tamu
    WHERE YEARWEEK(waktu_checkin, 1) = YEARWEEK(CURDATE(), 1)
")->fetchColumn();

$masih_di_dalam = $conn->query("
    SELECT COUNT(*)
    FROM tb_tamu
    WHERE status = 'di_dalam'
")->fetchColumn();

$total_staf = $conn->query("
    SELECT COUNT(*) FROM tb_staf
")->fetchColumn();

$total_user = $conn->query("
    SELECT COUNT(*) FROM tb_users
")->fetchColumn();

$base = '../';
$page_title = 'Dashboard Admin';

require_once('../inc/header.php');
?>


<div class="hotel-dashboard">

    <!-- Judul dashboard -->
    <div class="dashboard-top">
        <div>
            <div class="dashboard-eyebrow">Administration / Overview</div>
            <h1 class="dashboard-title">Ringkasan operasional.</h1>
            <p class="dashboard-description">
                Pantau aktivitas pengunjung dan kelola data dalam satu tempat.
            </p>
        </div>

        <div class="dashboard-date">
            <i class="far fa-calendar-alt"></i>
            <?= date('d/m/Y') ?>
        </div>
    </div>

    <!-- Sambutan -->
    <section class="welcome-panel">
        <div class="welcome-content">
            <div class="welcome-label">Guest Management System</div>
            <h2 class="welcome-title">Selamat datang, Admin.</h2>
            <p class="welcome-text">
                Sambutan yang baik dimulai dari pencatatan yang tertib.
                Berikut ringkasan aktivitas buku tamu hari ini.
            </p>
        </div>

        <div class="welcome-mark" aria-hidden="true">H</div>
    </section>

    <!-- Statistik -->
    <div class="section-heading">
        <h2 class="section-title">Ikhtisar pengunjung</h2>
        <span class="section-note">Data berdasarkan database</span>
    </div>

    <div class="stats-grid">

        <article class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Tamu hari ini</span>
                <span class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </span>
            </div>
            <p class="stat-number">
                <?= number_format((int) $total_hari_ini) ?>
            </p>
            <div class="stat-caption">Check-in pada hari ini</div>
            <div class="stat-accent"></div>
        </article>

        <article class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Tamu minggu ini</span>
                <span class="stat-icon">
                    <i class="far fa-calendar"></i>
                </span>
            </div>
            <p class="stat-number">
                <?= number_format((int) $total_minggu_ini) ?>
            </p>
            <div class="stat-caption">Akumulasi minggu berjalan</div>
            <div class="stat-accent"></div>
        </article>

        <article class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Masih di dalam</span>
                <span class="stat-icon">
                    <i class="fas fa-door-open"></i>
                </span>
            </div>
            <p class="stat-number">
                <?= number_format((int) $masih_di_dalam) ?>
            </p>
            <div class="stat-caption">Tamu berstatus di dalam</div>
            <div class="stat-accent"></div>
        </article>

        <article class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Staf / Pengguna</span>
                <span class="stat-icon">
                    <i class="fas fa-users"></i>
                </span>
            </div>
            <p class="stat-number">
                <?= number_format((int) $total_staf) ?>
                <small>/ <?= number_format((int) $total_user) ?></small>
            </p>
            <div class="stat-caption">Total staf dan akun pengguna</div>
            <div class="stat-accent"></div>
        </article>

    </div>

    <!-- Manajemen dan status -->
    <div class="bottom-grid">

        <section class="dashboard-panel">
            <div class="panel-header">
                <h3 class="panel-title">Manajemen data</h3>
                <i class="fas fa-arrow-right" style="color:#b49a6a;font-size:12px"></i>
            </div>

            <div class="panel-body">
                <p class="panel-description">
                    Akses cepat untuk mengelola data pendukung sistem buku tamu.
                </p>

                <a href="list_staf.php" class="management-link">
                    <span class="management-left">
                        <span class="management-icon">
                            <i class="fas fa-id-badge"></i>
                        </span>
                        <span>
                            <span class="management-name">Data staf</span>
                            <span class="management-description">
                                Kelola informasi dan daftar staf
                            </span>
                        </span>
                    </span>
                    <i class="fas fa-chevron-right management-arrow"></i>
                </a>

                <a href="list_user.php" class="management-link">
                    <span class="management-left">
                        <span class="management-icon">
                            <i class="fas fa-user-cog"></i>
                        </span>
                        <span>
                            <span class="management-name">Data pengguna</span>
                            <span class="management-description">
                                Kelola akun yang terdaftar di sistem
                            </span>
                        </span>
                    </span>
                    <i class="fas fa-chevron-right management-arrow"></i>
                </a>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="panel-header">
                <h3 class="panel-title">Status kunjungan</h3>
                <i class="fas fa-chart-pie" style="color:#b49a6a;font-size:13px"></i>
            </div>

            <div class="panel-body">
                <div class="occupancy-number">
                    <strong><?= number_format((int) $masih_di_dalam) ?></strong>
                    <span>tamu sedang berada di dalam</span>
                </div>

                <span class="occupancy-status">
                    <i class="fas fa-circle" style="font-size:7px"></i>
                    Data status terkini
                </span>

                <p class="occupancy-description">
                    Jumlah ini dihitung dari catatan tamu yang memiliki
                    status <strong>di_dalam</strong> pada database.
                    Pastikan proses check-out memperbarui status dengan benar.
                </p>
            </div>
        </section>

    </div>

    <footer class="dashboard-footer">
        <span>SISTEM INFORMASI BUKU TAMU</span>
        <span>Administration Panel &copy; <?= date('Y') ?></span>
    </footer>

</div>

<?php require_once('../inc/footer.php'); ?>