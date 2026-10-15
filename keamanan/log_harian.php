<?php
// Memanggil file otentikasi dan membatasi akses khusus untuk peran keamanan.
require_once('../inc/auth.php');
cek_role(['keamanan']);

// Memanggil file koneksi database.
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

// Fungsi pembersih karakter khusus untuk mencegah XSS.
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// Fungsi pembantu untuk memformat waktu jam masuk dan keluar.
function formatJam($value) {
    if (empty($value)) {
        return '-';
    }

    $timestamp = strtotime($value);
    return $timestamp !== false ? date('H:i', $timestamp) : '-';
}

// Menangkap parameter filter tanggal, atau menggunakan tanggal hari ini secara default.
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$date = DateTime::createFromFormat('!Y-m-d', $tanggal);

if (!$date || $date->format('Y-m-d') !== $tanggal) {
    $tanggal = date('Y-m-d');
}

// Pengaturan paginasi (5 data per halaman).
$perPage = 5;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;

$list_tamu = [];
$totalData = 0;
$totalPages = 1;

try {
    $mulai = $tanggal . ' 00:00:00';
    $akhir = date('Y-m-d H:i:s', strtotime($tanggal . ' +1 day'));

    // Menghitung jumlah total data kunjungan tamu pada tanggal yang dipilih.
    $countStmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM tb_tamu
         WHERE waktu_checkin >= :mulai AND waktu_checkin < :akhir"
    );

    $countStmt->execute([
        ':mulai' => $mulai,
        ':akhir' => $akhir
    ]);

    $totalData = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalData / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    // Query untuk mengambil riwayat log tamu berdasarkan tanggal yang dipilih beserta data staf tujuan.
    $query = "SELECT
                  t.*,
                  COALESCE(s.nama_staf, 'Staf tidak ditemukan') AS nama_staf
              FROM tb_tamu t
              LEFT JOIN tb_staf s ON t.staf_dituju_id = s.id
              WHERE t.waktu_checkin >= :mulai
                AND t.waktu_checkin < :akhir
              ORDER BY t.waktu_checkin DESC, t.id DESC
              LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':mulai', $mulai, PDO::PARAM_STR);
    $stmt->bindValue(':akhir', $akhir, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $list_tamu = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Jika terjadi error database, catat ke log server dan alihkan halaman.
    error_log('Log harian: ' . $e->getMessage());
    $_SESSION['message'] = 'Gagal mengambil log harian.';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$base = '../';
$page_title = 'Log Harian';
require_once('../inc/header.php');
?>

<link rel="stylesheet" href="../asset/css/style.css">

<div class="container-fluid px-0 log-page">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 log-heading">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-clock-history text-primary me-2"></i>
                Log Harian Tamu
            </h4>
            <p class="text-muted mb-0">Riwayat pengunjung berdasarkan tanggal check-in.</p>
        </div>
        <div class="text-muted small">
            <i class="bi bi-calendar3 me-1"></i>
            <?= e(date('d-m-Y', strtotime($tanggal))) ?>
        </div>
    </div>

    <!-- Menampilkan pesan pemberitahuan jika ada -->
    <?php if (!empty($_SESSION['message'])): ?>
    <div class="alert alert-<?= e($_SESSION['message_type'] ?? 'info') ?> alert-dismissible fade show">
        <?= e($_SESSION['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Formulir Filter Berdasarkan Tanggal -->
    <div class="card border-0 shadow-sm mb-4 log-card">
        <div class="card-body">
            <h6 class="fw-bold mb-3">
                <i class="bi bi-funnel me-2"></i>Filter Pengunjung
            </h6>

            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="tanggal" class="form-label fw-semibold">Tanggal Check-in</label>
                    <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?= e($tanggal) ?>"
                        required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Tampilkan
                    </button>
                </div>
                <div class="col-auto">
                    <a href="log_harian.php" class="btn btn-light border">
                        <i class="bi bi-calendar-check me-1"></i>Hari Ini
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm log-card">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="fw-bold mb-1">
                    <i class="bi bi-people text-primary me-2"></i>Daftar Pengunjung
                </h6>
                <small class="text-muted">
                    Data tamu pada tanggal <?= e(date('d-m-Y', strtotime($tanggal))) ?>
                </small>
            </div>
            <span class="badge bg-primary guest-count"><?= $totalData ?> tamu</span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Tamu</th>
                            <th>No. Identitas</th>
                            <th>No. Telepon</th>
                            <th>No. Badge</th>
                            <th>Staf Dituju</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$list_tamu): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                Tidak ada data tamu pada tanggal ini.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($list_tamu as $i => $t): ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td class="fw-semibold"><?= e($t['nama_tamu'] ?? '-') ?></td>
                            <td><?= e($t['no_identitas'] ?? '-') ?></td>
                            <td><?= e($t['no_telepon'] ?? '-') ?></td>
                            <td><?= e($t['no_badge'] ?? '-') ?></td>
                            <td><?= e($t['nama_staf'] ?? 'Staf tidak ditemukan') ?></td>
                            <td class="text-nowrap"><?= e(formatJam($t['waktu_checkin'] ?? null)) ?></td>
                            <td class="text-nowrap"><?= e(formatJam($t['waktu_checkout'] ?? null)) ?></td>
                            <td>
                                <!-- Menampilkan lencana warna berdasarkan status kunjungan tamu -->
                                <?php if (($t['status'] ?? '') === 'di_dalam'): ?>
                                <span class="badge bg-success">Di Dalam</span>
                                <?php elseif (($t['status'] ?? '') === 'sudah_keluar'): ?>
                                <span class="badge bg-secondary">Sudah Keluar</span>
                                <?php else: ?>
                                <span
                                    class="badge bg-warning text-dark"><?= e($t['status'] ?? 'Tidak diketahui') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
            <small class="text-muted">
                Menampilkan <?= count($list_tamu) ?> dari <?= $totalData ?> pengunjung
            </small>

            <?php if ($totalPages > 1): ?>

            <nav class="pagination-controls" aria-label="Navigasi halaman">
                <a class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="?page=<?= max(1, $page - 1) ?>"
                    aria-label="Halaman sebelumnya">
                    <i class="bi bi-chevron-left"></i>
                </a>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a class="pagination-btn <?= $p === $page ? 'active' : '' ?>" href="?page=<?= $p ?>"
                    <?= $p === $page ? 'aria-current="page"' : '' ?>><?= $p ?></a>
                <?php endfor; ?>

                <a class="pagination-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
                    href="?page=<?= min($totalPages, $page + 1) ?>" aria-label="Halaman berikutnya">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </nav>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once('../inc/footer.php'); ?>