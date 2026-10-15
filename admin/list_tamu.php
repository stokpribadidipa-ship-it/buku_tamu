<?php
require_once('../inc/auth.php');
cek_role(['admin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

$base = '../';
$page_title = 'Data Tamu';
$message = $_SESSION['message'] ?? '';
$message_type = 'success';
unset($_SESSION['message']);

if (empty($_SESSION['csrf_tamu'])) {
    $_SESSION['csrf_tamu'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_tamu'];

$loadError = false;
$tamu = [];
$totalData = 0;
$totalPages = 1;
$page = 1;
$perPage = 5;
$offset = 0;
$keyword = trim((string)($_GET['q'] ?? ''));
$tanggal = trim((string)($_GET['tanggal'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatNilai($value, string $column = ''): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    if (preg_match('/waktu|tanggal|created_at|updated_at|checkin|checkout/i', $column)) {
        $time = strtotime((string)$value);
        if ($time !== false) {
            return date('d/m/Y H:i', $time);
        }
    }

    return (string)$value;
}

function statusLabel($value): array
{
    $status = strtolower(trim((string)$value));

    if (in_array($status, ['di_dalam', 'di dalam', 'checkin', 'checked_in'], true)) {
        return ['Masih di dalam', 'inside'];
    }

    if (in_array($status, ['sudah_keluar', 'sudah keluar', 'keluar', 'checkout', 'checked_out'], true)) {
        return ['Sudah keluar', 'outside'];
    }

    return [ucwords(str_replace('_', ' ', $status ?: '-')), 'other'];
}

function formatLabel(string $column): string
{
    $labels = [
        'id' => 'ID Tamu',
        'id_tamu' => 'ID Tamu',
        'nama_tamu' => 'Nama Tamu',
        'no_identitas' => 'No. Identitas (NIK/SIM)',
        'no_telepon' => 'No. Telepon',
        'instansi' => 'Instansi / Perusahaan',
        'staf_dituju_id' => 'Staf yang Dituju',
        'no_badge' => 'No. Badge',
        'keperluan' => 'Keperluan',
        'waktu_checkin' => 'Waktu Check-in',
        'waktu_checkout' => 'Waktu Check-out',
        'status' => 'Status',
        'dicatat_oleh' => 'Dicatat Oleh',
        'diproses_keluar_oleh' => 'Diproses Keluar Oleh',
        'created_at' => 'Dibuat Pada',
        'updated_at' => 'Diperbarui Pada',
    ];

    return $labels[$column] ?? ucwords(str_replace('_', ' ', $column));
}

try {
    // Baca struktur tabel agar primary key bisa menyesuaikan database.
    $columnQuery = $conn->query('SHOW COLUMNS FROM tb_tamu');
    $columnRows = $columnQuery->fetchAll(PDO::FETCH_ASSOC);

    $columns = [];
    $columnTypes = [];
    $primaryKey = null;

    foreach ($columnRows as $column) {
        $name = $column['Field'];
        $columns[] = $name;
        $columnTypes[$name] = strtolower($column['Type']);

        if ($column['Key'] === 'PRI' && $primaryKey === null) {
            $primaryKey = $name;
        }
    }

    if ($primaryKey === null) {
        foreach (['id_tamu', 'id', 'id_pengunjung'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $primaryKey = $candidate;
                break;
            }
        }
    }

    // Pastikan kolom inti form tersedia.
    $requiredColumns = [
        'nama_tamu', 'no_identitas', 'no_telepon', 'instansi',
        'staf_dituju_id', 'no_badge', 'keperluan', 'waktu_checkin',
        'waktu_checkout', 'status', 'dicatat_oleh', 'diproses_keluar_oleh'
    ];
    $missingColumns = array_values(array_diff($requiredColumns, $columns));

    // Pencarian hanya pada kolom teks yang memang ada.
    $searchColumns = [];
    foreach ($columns as $column) {
        $type = $columnTypes[$column] ?? '';
        if (
            strpos($type, 'char') !== false ||
            strpos($type, 'text') !== false ||
            strpos($type, 'enum') !== false
        ) {
            $searchColumns[] = $column;
        }
    }

    // Hapus data dengan token CSRF.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_tamu'])) {
        $postedToken = $_POST['csrf_token'] ?? '';
        $id = $_POST['id_tamu'] ?? '';

        if (!is_string($postedToken) || !hash_equals($csrf_token, $postedToken)) {
            $message = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
            $message_type = 'danger';
        } elseif (!$primaryKey) {
            $message = 'Data tidak dapat dihapus karena primary key tidak ditemukan.';
            $message_type = 'danger';
        } elseif (!is_scalar($id) || trim((string)$id) === '') {
            $message = 'ID tamu tidak valid.';
            $message_type = 'danger';
        } else {
            $deleteQuery = $conn->prepare("DELETE FROM tb_tamu WHERE `$primaryKey` = :id");
            $deleteQuery->execute([':id' => $id]);

            if ($deleteQuery->rowCount() > 0) {
                $message = 'Data tamu berhasil dihapus.';
                $message_type = 'success';
            } else {
                $message = 'Data tidak ditemukan atau sudah dihapus.';
                $message_type = 'warning';
            }
        }
    }

    // Filter tanggal.
    $where = [];
    $params = [];

    if ($keyword !== '' && $searchColumns) {
        $conditions = [];
        foreach ($searchColumns as $index => $column) {
            $param = ':search' . $index;
            $conditions[] = "t.`$column` LIKE $param";
            $params[$param] = '%' . $keyword . '%';
        }
        $where[] = '(' . implode(' OR ', $conditions) . ')';
    }

    if (
        $tanggal !== '' &&
        in_array('waktu_checkin', $columns, true) &&
        preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)
    ) {
        $dateObject = DateTime::createFromFormat('!Y-m-d', $tanggal);
        if ($dateObject && $dateObject->format('Y-m-d') === $tanggal) {
            $where[] = 'DATE(t.`waktu_checkin`) = :tanggal';
            $params[':tanggal'] = $tanggal;
        } else {
            $tanggal = '';
        }
    } else {
        $tanggal = '';
    }

    if (
        $statusFilter !== '' &&
        in_array('status', $columns, true) &&
        in_array($statusFilter, ['di_dalam', 'sudah_keluar'], true)
    ) {
        $where[] = 't.`status` = :status';
        $params[':status'] = $statusFilter;
    } else {
        $statusFilter = '';
    }

    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $countQuery = $conn->prepare('SELECT COUNT(*) FROM tb_tamu AS t' . $whereSql);
    $countQuery->execute($params);
    $totalData = (int)$countQuery->fetchColumn();

    $totalPages = max(1, (int)ceil($totalData / $perPage));
    $pageInput = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
    $page = ($pageInput && $pageInput > 0) ? $pageInput : 1;
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    // Ambil seluruh kolom tamu agar modal detail tidak kehilangan nilai.
    // LEFT JOIN mengambil nama staf/petugas, bukan hanya ID relasi.
    $dataSql = "SELECT
                    t.*,
                    s.nama_staf AS _nama_staf_dituju,
                    s.departemen AS _departemen_staf,
                    u1.nama AS _nama_pencatat,
                    u2.nama AS _nama_petugas_checkout
                FROM tb_tamu AS t
                LEFT JOIN tb_staf AS s ON s.id = t.staf_dituju_id
                LEFT JOIN tb_users AS u1 ON u1.id = t.dicatat_oleh
                LEFT JOIN tb_users AS u2 ON u2.id = t.diproses_keluar_oleh"
                . $whereSql .
                ($primaryKey ? " ORDER BY t.`$primaryKey` DESC" : '') .
                " LIMIT $perPage OFFSET $offset";

    $dataQuery = $conn->prepare($dataSql);
    $dataQuery->execute($params);
    $tamu = $dataQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Data tamu: ' . $e->getMessage());
    $loadError = true;
    $tamu = [];
    $columns = [];
    $primaryKey = null;
    $totalData = 0;
    $totalPages = 1;
    $page = 1;
    $offset = 0;
    $message = 'Data tamu gagal dimuat. Periksa koneksi database dan struktur tabel.';
    $message_type = 'danger';
}

$queryParams = $_GET;
unset($queryParams['page']);

require_once('../inc/header.php');
?>


<div class="guest-page">
    <div class="mb-4">
        <div class="guest-eyebrow">Hotel / Administrasi</div>
        <h1 class="guest-title">Data Tamu</h1>
        <p class="guest-subtitle">Kelola dan pantau seluruh catatan kunjungan tamu.</p>
    </div>

    <?php if ($message !== ''): ?>
    <div class="alert alert-<?= e($message_type) ?> alert-dismissible fade show" role="alert">
        <?= e($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    <?php endif; ?>

    <?php if (!$loadError): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="guest-stat">
                <div class="guest-stat-label">Total hasil pencarian</div>
                <div class="guest-stat-number"><?= number_format($totalData, 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="guest-stat">
                <div class="guest-stat-label">Halaman aktif</div>
                <div class="guest-stat-number"><?= $page ?> / <?= $totalPages ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="guest-stat">
                <div class="guest-stat-label">Data per halaman</div>
                <div class="guest-stat-number"><?= $perPage ?></div>
            </div>
        </div>
    </div>

    <section class="guest-panel">
        <div class="guest-panel-heading">
            <h5><i class="fas fa-address-book me-2"></i>Daftar Kunjungan</h5>
            <span class="guest-count">Data dari database</span>
        </div>

        <div class="guest-panel-body">
            <form method="GET" action="" class="row g-2 align-items-end">
                <div class="col-lg-5 col-md-12">
                    <label for="q" class="form-label small">Cari tamu</label>
                    <input type="search" id="q" name="q" class="form-control"
                        placeholder="Nama, identitas, telepon, instansi..." value="<?= e($keyword) ?>">
                </div>
                <div class="col-lg-3 col-md-5">
                    <label for="tanggal" class="form-label small">Tanggal check-in</label>
                    <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?= e($tanggal) ?>">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label for="status" class="form-label small">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Semua status</option>
                        <option value="di_dalam" <?= $statusFilter === 'di_dalam' ? 'selected' : '' ?>>Masih di dalam
                        </option>
                        <option value="sudah_keluar" <?= $statusFilter === 'sudah_keluar' ? 'selected' : '' ?>>Sudah
                            keluar</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-olive flex-grow-1">
                        <i class="fas fa-search me-1"></i>Cari
                    </button>
                    <a href="list_tamu.php" class="btn btn-outline-secondary" title="Reset filter">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table guest-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Tamu</th>
                        <th>No. Identitas</th>
                        <th>No. Telepon</th>
                        <th>Instansi</th>
                        <th>Staf yang Dituju</th>
                        <th>No. Badge</th>
                        <th>Keperluan</th>
                        <th>Waktu Check-in</th>
                        <th>Waktu Check-out</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tamu)): ?>
                    <tr>
                        <td colspan="12">
                            <div class="guest-empty">
                                <div><i class="far fa-folder-open"></i></div>
                                <strong>Belum ada data tamu</strong>
                                <p class="mb-0 mt-2">Data tidak ditemukan atau belum tercatat di database.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($tamu as $index => $row): ?>
                    <?php
                            $idTamu = $primaryKey ? ($row[$primaryKey] ?? '') : '';
                            $modalId = 'detailTamu' . (int)$index;
                            [$labelStatus, $classStatus] = statusLabel($row['status'] ?? '');
                            ?>
                    <tr>
                        <td><?= $offset + $index + 1 ?></td>
                        <td><strong><?= e($row['nama_tamu'] ?? '-') ?></strong></td>
                        <td><?= e($row['no_identitas'] ?? '-') ?></td>
                        <td><?= e($row['no_telepon'] ?? '-') ?></td>
                        <td><?= e($row['instansi'] ?? '-') ?></td>
                        <td><?= e($row['_nama_staf_dituju'] ?? '-') ?></td>
                        <td><?= e($row['no_badge'] ?? '-') ?></td>
                        <td><?= e($row['keperluan'] ?? '-') ?></td>
                        <td><?= e(formatNilai($row['waktu_checkin'] ?? null, 'waktu_checkin')) ?></td>
                        <td><?= e(formatNilai($row['waktu_checkout'] ?? null, 'waktu_checkout')) ?></td>
                        <td><span class="guest-status <?= e($classStatus) ?>"><?= e($labelStatus) ?></span></td>
                        <td>
                            <div class="guest-actions">
                                <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal"
                                    data-bs-target="#<?= e($modalId) ?>" title="Lihat detail"
                                    aria-label="Lihat detail tamu">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($primaryKey !== null): ?>
                                <form method="POST" action=""
                                    onsubmit="return confirm('Yakin ingin menghapus data tamu ini? Data yang dihapus tidak dapat dikembalikan.');">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                    <input type="hidden" name="id_tamu" value="<?= e($idTamu) ?>">
                                    <button type="submit" name="hapus_tamu" value="1"
                                        class="btn btn-sm btn-outline-danger" title="Hapus data">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal detail: gunakan row hasil t.* agar semua data form terbaca -->
                    <div class="modal fade" id="<?= e($modalId) ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-user-check me-2"></i>Detail Tamu</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body">
                                    <dl class="row guest-detail mb-0">
                                        <dt class="col-sm-5">ID Tamu</dt>
                                        <dd class="col-sm-7"><?= e($idTamu ?: '-') ?></dd>

                                        <dt class="col-sm-5">Nama Tamu</dt>
                                        <dd class="col-sm-7"><?= e($row['nama_tamu'] ?? '-') ?></dd>

                                        <dt class="col-sm-5">No. Identitas (NIK/SIM)</dt>
                                        <dd class="col-sm-7"><?= e($row['no_identitas'] ?? '-') ?></dd>

                                        <dt class="col-sm-5">No. Telepon</dt>
                                        <dd class="col-sm-7"><?= e($row['no_telepon'] ?? '-') ?></dd>

                                        <dt class="col-sm-5">Instansi / Perusahaan</dt>
                                        <dd class="col-sm-7"><?= e($row['instansi'] ?? '-') ?></dd>

                                        <dt class="col-sm-5">Staf yang Dituju</dt>
                                        <dd class="col-sm-7"><?= e($row['_nama_staf_dituju'] ?? '-') ?></dd>

                                        <dt class="col-sm-5">Departemen Staf</dt>
                                        <dd class="col-sm-7"><?= e($row['_departemen_staf'] ?? '-') ?></dd>

                                        <dt class="col-sm-5">No. Badge</dt>
                                        <dd class="col-sm-7"><?= e($row['no_badge'] ?? '-') ?></dd>

                                        <dt class="col-sm-5">Keperluan</dt>
                                        <dd class="col-sm-7 text-break"><?= nl2br(e($row['keperluan'] ?? '-')) ?></dd>

                                        <dt class="col-sm-5">Waktu Check-in</dt>
                                        <dd class="col-sm-7">
                                            <?= e(formatNilai($row['waktu_checkin'] ?? null, 'waktu_checkin')) ?></dd>

                                        <dt class="col-sm-5">Waktu Check-out</dt>
                                        <dd class="col-sm-7">
                                            <?= e(formatNilai($row['waktu_checkout'] ?? null, 'waktu_checkout')) ?></dd>

                                        <dt class="col-sm-5">Status</dt>
                                        <dd class="col-sm-7"><span
                                                class="guest-status <?= e($classStatus) ?>"><?= e($labelStatus) ?></span>
                                        </dd>

                                        <dt class="col-sm-5">Dicatat Oleh</dt>
                                        <dd class="col-sm-7"><?= e($row['_nama_pencatat'] ?? '-') ?></dd>

                                        <dt class="col-sm-5">Diproses Keluar Oleh</dt>
                                        <dd class="col-sm-7"><?= e($row['_nama_petugas_checkout'] ?? '-') ?></dd>

                                        <dt class="col-sm-5">Dibuat Pada</dt>
                                        <dd class="col-sm-7">
                                            <?= e(formatNilai($row['created_at'] ?? null, 'created_at')) ?></dd>
                                    </dl>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="guest-pagination">
            <div class="guest-count">
                <?php if ($totalData > 0): ?>
                Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalData) ?>
                dari <?= number_format($totalData, 0, ',', '.') ?> data
                <?php else: ?>
                Tidak ada data untuk ditampilkan
                <?php endif; ?>
            </div>
            <div>
                <?php
                    $previousParams = $queryParams;
                    $previousParams['page'] = max(1, $page - 1);
                    $previousUrl = '?' . http_build_query($previousParams);

                    $nextParams = $queryParams;
                    $nextParams['page'] = min($totalPages, $page + 1);
                    $nextUrl = '?' . http_build_query($nextParams);
                    ?>
                <a href="<?= e($previousUrl) ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>"
                    aria-label="Halaman sebelumnya">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <span class="current"><?= $page ?></span>
                <a href="<?= e($nextUrl) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>"
                    aria-label="Halaman berikutnya">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <div class="text-muted small mt-4">
        <i class="fas fa-info-circle me-1"></i>
        Halaman ini menampilkan catatan kunjungan yang tersimpan di database.
    </div>
</div>

<?php require_once('../inc/footer.php'); ?>