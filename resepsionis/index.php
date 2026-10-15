<?php
// Memanggil file pengaman otentikasi agar halaman ini hanya bisa diakses oleh pengguna yang login.
require_once('../inc/auth.php');
// Membatasi hak akses halaman ini khusus untuk peran (role) 'resepsionis' saja.
cek_role(['resepsionis']);
// Memanggil file koneksi database.
require_once('../inc/koneksi.php');

// Membuat objek koneksi database baru.
$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

// Mengatur direktori utama relatif ($base) dan judul halaman web.
$base = '../';
$page_title = 'Dashboard Resepsionis';

// Fungsi khusus untuk membersihkan teks dari karakter khusus agar aman dari celah XSS (Cross-Site Scripting).
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Menyiapkan variabel penampung untuk pencarian data (filter)
$keyword = trim((string)($_GET['q'] ?? ''));
$tanggal = trim((string)($_GET['tanggal'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));

// Pengaturan penomoran halaman (pagination): menetapkan 5 data per halaman.
$perPage = 5;
$pageInput = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($pageInput && $pageInput > 0) ? $pageInput : 1;

$list_tamu = [];
$totalData = 0;
$totalPages = 1;
$loadError = false;

try {
    // Menyusun kondisi pencarian (WHERE): hanya menampilkan tamu yang dicatat oleh resepsionis yang sedang login saat ini.
    $where = [
        't.dicatat_oleh = :uid'
    ];

    $params = [
        ':uid' => $_SESSION['id']
    ];

    // Jika ada kata kunci pencarian (nama tamu, nomor badge, atau nama staf).
    if ($keyword !== '') {
        $where[] = '(
            t.nama_tamu LIKE :keyword_nama
            OR t.no_badge LIKE :keyword_badge
            OR s.nama_staf LIKE :keyword_staf
        )';

        $search = '%' . $keyword . '%';
        $params[':keyword_nama'] = $search;
        $params[':keyword_badge'] = $search;
        $params[':keyword_staf'] = $search;
    }

    // Jika filter berdasarkan tanggal check-in diisi dan formatnya benar (YYYY-MM-DD).
    if (
        $tanggal !== '' &&
        preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)
    ) {
        $dateObject = DateTime::createFromFormat('!Y-m-d', $tanggal);

        if ($dateObject && $dateObject->format('Y-m-d') === $tanggal) {
            $where[] = 'DATE(t.waktu_checkin) = :tanggal';
            $params[':tanggal'] = $tanggal;
        } else {
            $tanggal = '';
        }
    } else {
        $tanggal = '';
    }

    // Jika filter status tamu dipilih (di_dalam atau sudah_keluar).
    if (
        $statusFilter !== '' &&
        in_array($statusFilter, ['di_dalam', 'sudah_keluar'], true)
    ) {
        $where[] = 't.status = :status';
        $params[':status'] = $statusFilter;
    } else {
        $statusFilter = '';
    }

    // Menggabungkan semua syarat filter menjadi satu kalimat SQL WHERE.
    $whereSql = ' WHERE ' . implode(' AND ', $where);

    // Menghitung jumlah total data tamu yang cocok dengan filter pencarian untuk keperluan pagination.
    $countSql = "
        SELECT COUNT(*)
        FROM tb_tamu t
        LEFT JOIN tb_staf s ON s.id = t.staf_dituju_id
        $whereSql
    ";

    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($params);
    $totalData = (int) $countStmt->fetchColumn();

    // Menghitung jumlah halaman maksimal dan menentukan posisi offset data.
    $totalPages = max(1, (int) ceil($totalData / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    // Menjalankan query untuk mengambil data tamu lengkap dengan waktu check-out dan nama staf yang dituju.
    $dataSql = "
        SELECT
            t.id,
            t.nama_tamu,
            t.no_badge,
            t.waktu_checkin,
            t.waktu_checkout,
            t.status,
            s.nama_staf
        FROM tb_tamu t
        LEFT JOIN tb_staf s ON s.id = t.staf_dituju_id
        $whereSql
        ORDER BY t.waktu_checkin DESC
        LIMIT $perPage OFFSET $offset
    ";

    $dataStmt = $conn->prepare($dataSql);
    $dataStmt->execute($params);
    $list_tamu = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {
    // Jika terjadi error pada database, simpan ke log server dan tandai gagal muat.
    error_log('Dashboard resepsionis: ' . $ex->getMessage());
    $loadError = true;
}

// Memanggil bagian atas halaman (header web).
require_once('../inc/header.php');

// Mengatur parameter URL untuk tombol navigasi halaman sebelumnya dan berikutnya.
$queryParams = $_GET;
unset($queryParams['page']);

$previousParams = $queryParams;
$previousParams['page'] = max(1, $page - 1);
$previousUrl = '?' . http_build_query($previousParams);

$nextParams = $queryParams;
$nextParams['page'] = min($totalPages, $page + 1);
$nextUrl = '?' . http_build_query($nextParams);
?>
<link rel="stylesheet" href="<?= $base ?>asset/css/resepsionis.css">

<div class="reception-page">

    <!-- Bagian judul halaman dashboard resepsionis -->
    <div class="reception-heading">
        <div>
            <div class="reception-eyebrow">Resepsionis / Ringkasan</div>
            <h1 class="reception-title">Tamu Dicatat</h1>
            <p class="reception-subtitle">
                Pantau kunjungan yang dicatat oleh akun Anda.
            </p>
        </div>

        <!-- Tombol pintasan menuju halaman form pencatatan tamu baru -->
        <a href="tamu_masuk.php" class="reception-add">
            <i class="fas fa-plus me-2"></i>Catat Tamu Baru
        </a>
    </div>

    <?php if ($loadError): ?>
    <!-- Pesan peringatan jika data gagal dimuat dari database -->
    <div class="alert alert-danger">
        Data tamu gagal dimuat. Periksa koneksi database dan struktur tabel.
    </div>
    <?php else: ?>

    <section class="reception-panel">

        <div class="reception-panel-heading">
            <div>
                <h5>
                    <i class="fas fa-users me-2"></i>
                    Daftar Kunjungan
                </h5>
                <p>Data sesuai pencarian dan filter yang dipilih.</p>
            </div>

            <!-- Menampilkan total jumlah tamu yang ditemukan -->
            <span class="reception-count-badge">
                <i class="fas fa-user-check me-1"></i>
                <?= number_format($totalData, 0, ',', '.') ?> tamu
            </span>
        </div>

        <!-- Formulir Filter dan Pencarian Data Tamu -->
        <div class="reception-filter">
            <form method="GET" action="index.php">
                <div class="reception-filter-grid">

                    <div>
                        <label for="q" class="form-label">Cari tamu</label>
                        <input type="search" id="q" name="q" class="form-control"
                            placeholder="Nama, nomor badge, atau staf..." value="<?= e($keyword) ?>">
                    </div>

                    <div>
                        <label for="tanggal" class="form-label">
                            Tanggal check-in
                        </label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?= e($tanggal) ?>">
                    </div>

                    <div>
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">Semua status</option>
                            <option value="di_dalam" <?= $statusFilter === 'di_dalam' ? 'selected' : '' ?>>
                                Di dalam
                            </option>
                            <option value="sudah_keluar" <?= $statusFilter === 'sudah_keluar' ? 'selected' : '' ?>>
                                Sudah keluar
                            </option>
                        </select>
                    </div>

                    <div class="reception-filter-actions">
                        <button type="submit" class="btn reception-search-btn">
                            <i class="fas fa-search me-1"></i> Cari
                        </button>

                        <a href="index.php" class="reception-reset-btn" title="Reset filter" aria-label="Reset filter">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>

                </div>
            </form>
        </div>

        <!-- Tabel Daftar Tamu -->
        <div class="reception-table-wrap">
            <table class="table reception-table align-middle">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Tamu</th>
                        <th>No. Badge</th>
                        <th>Staf Dituju</th>
                        <th>Jam Masuk</th>
                        <th>Jam Keluar</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($list_tamu)): ?>
                    <tr>
                        <td colspan="7" class="reception-empty">
                            <i class="far fa-folder-open"></i>
                            <strong>Data tamu tidak ditemukan</strong>
                            <div class="mt-1">
                                Coba ubah kata kunci atau filter pencarian.
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($list_tamu as $index => $t): ?>
                    <?php
                            $status = strtolower(trim((string)($t['status'] ?? '')));
                            $sudahKeluar = in_array(
                                $status,
                                ['sudah_keluar', 'sudah keluar', 'keluar', 'checkout', 'checked_out'],
                                true
                            );

                            $jamMasuk = !empty($t['waktu_checkin'])
                                ? date('H:i', strtotime($t['waktu_checkin']))
                                : '—';

                            $jamKeluar = !empty($t['waktu_checkout'])
                                ? date('H:i', strtotime($t['waktu_checkout']))
                                : '—';
                            ?>

                    <tr>
                        <td class="reception-number">
                            <?= $offset + $index + 1 ?>
                        </td>

                        <td>
                            <span class="reception-guest-name">
                                <?= e($t['nama_tamu'] ?? '-') ?>
                            </span>
                        </td>

                        <td>
                            <span class="reception-badge">
                                <?= e($t['no_badge'] ?? '-') ?>
                            </span>
                        </td>

                        <td><?= e($t['nama_staf'] ?? '-') ?></td>

                        <td>
                            <span class="reception-time">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                <?= e($jamMasuk) ?>
                            </span>
                        </td>

                        <td>
                            <?php if (!empty($t['waktu_checkout'])): ?>
                            <span class="reception-time out">
                                <i class="fas fa-sign-out-alt me-1"></i>
                                <?= e($jamKeluar) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($sudahKeluar): ?>
                            <span class="reception-status outside">
                                <i class="fas fa-check-circle me-1"></i>
                                Sudah Keluar
                            </span>
                            <?php else: ?>
                            <span class="reception-status inside">
                                <i class="fas fa-user-check me-1"></i>
                                Di Dalam
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Bagian Navigasi Halaman (Pagination) -->
        <div class="reception-pagination">
            <div class="reception-pagination-info">
                <?php if ($totalData > 0): ?>
                Menampilkan
                <strong><?= $offset + 1 ?>–<?= min($offset + $perPage, $totalData) ?></strong>
                dari <strong><?= number_format($totalData, 0, ',', '.') ?></strong> tamu
                <span class="mx-1">·</span>
                5 data per halaman
                <?php else: ?>
                Tidak ada data untuk ditampilkan
                <?php endif; ?>
            </div>

            <nav class="reception-pagination-controls" aria-label="Navigasi halaman">
                <a href="<?= e($previousUrl) ?>" class="reception-page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
                    aria-label="Halaman sebelumnya" <?= $page <= 1 ? 'aria-disabled="true"' : '' ?>>
                    <i class="fas fa-chevron-left"></i>
                </a>

                <span class="reception-page-current" aria-current="page">
                    <?= $page ?>
                </span>

                <a href="<?= e($nextUrl) ?>" class="reception-page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
                    aria-label="Halaman berikutnya" <?= $page >= $totalPages ? 'aria-disabled="true"' : '' ?>>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </nav>
        </div>

    </section>
    <?php endif; ?>

    <div class="text-muted small mt-3">
        <i class="fas fa-info-circle me-1"></i>
        Jam keluar akan muncul setelah proses check-out tercatat di database.
    </div>

</div>

<?php require_once('../inc/footer.php'); ?>