<?php
// Memanggil otentikasi dan membatasi akses khusus keamanan.
require_once('../inc/auth.php');
cek_role(['keamanan']);

// Memanggil koneksi database.
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

// Fungsi untuk mencegah XSS saat menampilkan data.
if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

// Mengambil pesan notifikasi.
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['message_type'] ?? 'info';

unset($_SESSION['message'], $_SESSION['message_type']);

// Pengaturan pagination.
$perPage = 5;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;

$statistik = [
    'total' => 0,
    'di_dalam' => 0,
    'sudah_keluar' => 0
];

$list_tamu = [];
$totalData = 0;
$totalPages = 1;
$offset = 0;

try {
    // Mengambil statistik kunjungan tamu hari ini.
    $hariIni = date('Y-m-d');

    $stat = $conn->prepare(
        "SELECT
            COUNT(*) AS total,
            COALESCE(
                SUM(CASE WHEN status = 'di_dalam' THEN 1 ELSE 0 END),
                0
            ) AS di_dalam,
            COALESCE(
                SUM(CASE WHEN status = 'sudah_keluar' THEN 1 ELSE 0 END),
                0
            ) AS sudah_keluar
         FROM tb_tamu
         WHERE waktu_checkin >= :mulai
           AND waktu_checkin < :akhir"
    );

    $stat->execute([
        ':mulai' => $hariIni . ' 00:00:00',
        ':akhir' => date(
            'Y-m-d H:i:s',
            strtotime($hariIni . ' +1 day')
        )
    ]);

    $statistik = $stat->fetch(PDO::FETCH_ASSOC) ?: $statistik;

    // Menghitung jumlah tamu yang masih berada di dalam gedung.
    $countStmt = $conn->query(
        "SELECT COUNT(*)
         FROM tb_tamu
         WHERE status = 'di_dalam'"
    );

    $totalData = (int) $countStmt->fetchColumn();

    // Menghitung jumlah halaman.
    $totalPages = max(
        1,
        (int) ceil($totalData / $perPage)
    );

    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    // Mengambil daftar tamu aktif beserta staf yang dituju.
    $query = "SELECT
                  t.id,
                  t.nama_tamu,
                  t.no_identitas,
                  t.no_telepon,
                  t.no_badge,
                  t.waktu_checkin,
                  t.staf_dituju_id,
                  COALESCE(
                      s.nama_staf,
                      'Staf tidak ditemukan'
                  ) AS nama_staf
              FROM tb_tamu t
              LEFT JOIN tb_staf s
                  ON t.staf_dituju_id = s.id
              WHERE t.status = 'di_dalam'
              ORDER BY t.waktu_checkin DESC, t.id DESC
              LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($query);

    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();

    $list_tamu = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Mencatat detail error di log server.
    error_log('Dashboard keamanan: ' . $e->getMessage());

    $message = 'Gagal mengambil data dashboard. Periksa database.';
    $messageType = 'danger';
}

// Pengaturan layout.
$base = '../';
$page_title = 'Dashboard Keamanan';

require_once('../inc/header.php');
?>

<link rel="stylesheet" href="../asset/css/style.css">

<div class="container-fluid px-0 dashboard-content">

    <!-- Judul dashboard -->
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Dashboard Keamanan</h4>
        <p class="text-muted mb-0">
            Pantau pengunjung yang sedang berada di gedung.
        </p>
    </div>

    <!-- Notifikasi -->
    <?php if ($message !== ''): ?>
    <div class="alert alert-<?= e($messageType) ?> alert-dismissible fade show" role="alert">

        <?= e($message) ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup">
        </button>
    </div>
    <?php endif; ?>

    <!-- Statistik kunjungan -->
    <div class="row g-3 mb-4">

        <div class="col-sm-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">

                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people fs-3"></i>
                    </div>

                    <div>
                        <div class="text-muted small">
                            Total Tamu Hari Ini
                        </div>

                        <h3 class="fw-bold mb-0">
                            <?= (int) ($statistik['total'] ?? 0) ?>
                        </h3>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">

                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-person-check fs-3"></i>
                    </div>

                    <div>
                        <div class="text-muted small">
                            Masih di Dalam
                        </div>

                        <h3 class="fw-bold mb-0">
                            <?= (int) ($statistik['di_dalam'] ?? 0) ?>
                        </h3>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">

                    <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-person-check-fill fs-3"></i>
                    </div>

                    <div>
                        <div class="text-muted small">
                            Sudah Check-out
                        </div>

                        <h3 class="fw-bold mb-0">
                            <?= (int) ($statistik['sudah_keluar'] ?? 0) ?>
                        </h3>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Tabel tamu aktif -->
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                <div>
                    <h6 class="fw-bold mb-1">
                        <i class="bi bi-person-lines-fill me-2"></i>
                        Tamu yang Masih di Dalam Gedung
                    </h6>

                    <small class="text-muted">
                        Tamu terbaru ditampilkan di urutan paling atas.
                    </small>
                </div>

                <span class="badge bg-success rounded-pill px-3 py-2">
                    <i class="bi bi-people me-1"></i>
                    <?= $totalData ?> tamu aktif
                </span>

            </div>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Tamu</th>
                            <th>No. Identitas (KTP/SIM)</th>
                            <th>No. Telepon</th>
                            <th>No. Badge</th>
                            <th>Staf Dituju</th>
                            <th>Jam Masuk</th>
                            <th>Lama di Dalam</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!$list_tamu): ?>

                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">

                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>

                                <div class="fw-semibold">
                                    Belum ada tamu aktif.
                                </div>

                                <small>
                                    Tamu yang belum check-out akan tampil di sini.
                                </small>

                            </td>
                        </tr>

                        <?php else: ?>

                        <?php foreach ($list_tamu as $i => $t): ?>

                        <?php
                                // Menghitung durasi tamu berada di gedung.
                                $masuk = !empty($t['waktu_checkin'])
                                    ? strtotime($t['waktu_checkin'])
                                    : false;

                                $menit = $masuk !== false
                                    ? max(
                                        0,
                                        (int) floor(
                                            (time() - $masuk) / 60
                                        )
                                    )
                                    : 0;

                                $durasi = $menit >= 60
                                    ? floor($menit / 60) . ' jam '
                                        . ($menit % 60) . ' mnt'
                                    : $menit . ' mnt';
                                ?>

                        <tr>

                            <td>
                                <?= $offset + $i + 1 ?>
                            </td>

                            <td class="fw-semibold">
                                <?= e($t['nama_tamu']) ?>
                            </td>

                            <td>
                                <?= e($t['no_identitas']) ?>
                            </td>

                            <td>
                                <?= e($t['no_telepon']) ?>
                            </td>

                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= e($t['no_badge']) ?>
                                </span>
                            </td>

                            <td>
                                <?= e($t['nama_staf']) ?>
                            </td>

                            <td class="text-nowrap">
                                <?= $masuk !== false
                                            ? date('H:i', $masuk)
                                            : '-' ?>
                            </td>

                            <td class="text-nowrap">
                                <?= e($durasi) ?>

                                <?php if ($menit > 120): ?>
                                <span class="badge bg-danger ms-1">
                                    Lama
                                </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="d-flex justify-content-center gap-2">

                                    <!-- Tombol edit -->
                                    <a href="edit_tamu.php?id=<?= (int) $t['id'] ?>" class="btn btn-sm btn-primary"
                                        title="Edit tamu" aria-label="Edit tamu">

                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <!-- Form checkout -->
                                    <form action="checkout_act.php" method="POST" class="d-inline checkout-form">

                                        <?= csrf_field() ?>

                                        <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">

                                        <button type="submit" class="btn btn-sm btn-secondary" title="Check-out tamu"
                                            aria-label="Check-out tamu">

                                            <i class="bi bi-box-arrow-right"></i>
                                        </button>

                                    </form>

                                </div>
                            </td>

                        </tr>

                        <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </div>

        <!-- Footer dan pagination -->
        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">

            <small class="text-muted">
                Menampilkan <?= count($list_tamu) ?>
                dari <?= $totalData ?> tamu aktif
            </small>

            <?php if ($totalPages > 1): ?>

            <nav class="pagination-controls" aria-label="Navigasi halaman">

                <a class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="?page=<?= max(1, $page - 1) ?>"
                    aria-label="Halaman sebelumnya">

                    <i class="bi bi-chevron-left"></i>
                </a>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>

                <a class="pagination-btn <?= $p === $page ? 'active' : '' ?>" href="?page=<?= $p ?>"
                    <?= $p === $page ? 'aria-current="page"' : '' ?>>

                    <?= $p ?>
                </a>

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


<!-- ==========================================
     MODAL KONFIRMASI CHECK-OUT
========================================== -->

<div class="modal fade" id="modalCheckout" tabindex="-1" aria-labelledby="judulModalCheckout" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow-lg checkout-modal-content">

            <div class="modal-body text-center p-4 p-md-5">

                <!-- Ikon modal -->
                <div class="checkout-modal-icon mx-auto mb-3">
                    <i class="bi bi-box-arrow-right"></i>
                </div>

                <h5 class="fw-bold mb-2" id="judulModalCheckout">

                    Konfirmasi Check-out
                </h5>

                <p class="text-muted mb-4">
                    Apakah kamu yakin ingin memproses check-out tamu ini?
                </p>

                <!-- Informasi konfirmasi -->
                <div class="checkout-info text-start mb-4">

                    <div class="d-flex align-items-start gap-2">

                        <i class="bi bi-info-circle-fill text-primary mt-1"></i>

                        <small class="text-secondary">
                            Pastikan tamu benar-benar akan meninggalkan
                            gedung sebelum melanjutkan proses check-out.
                        </small>

                    </div>

                </div>

                <!-- Tombol modal -->
                <div class="d-flex gap-2">

                    <button type="button" class="btn btn-light border flex-fill py-2" data-bs-dismiss="modal">

                        <i class="bi bi-x-lg me-1"></i>
                        Batal
                    </button>

                    <button type="button" class="btn btn-primary flex-fill py-2" id="btnKonfirmasiCheckout">

                        <i class="bi bi-check-circle me-1"></i>
                        Ya, Check-out
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- ==========================================
     CSS KHUSUS MODAL CHECK-OUT
========================================== -->

<style>
.checkout-modal-content {
    border-radius: 18px;
    overflow: hidden;
}

.checkout-modal-icon {
    width: 78px;
    height: 78px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d97706;
    background: #fff7e6;
    font-size: 2.3rem;
}

.checkout-info {
    padding: 14px 16px;
    border: 1px solid #e8edf3;
    border-radius: 12px;
    background: #f8fafc;
}

#btnKonfirmasiCheckout,
#modalCheckout .btn {
    border-radius: 9px;
    font-weight: 500;
    transition: all 0.2s ease;
}

#btnKonfirmasiCheckout:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}

#modalCheckout .btn:focus-visible {
    outline: 3px solid rgba(13, 110, 253, 0.25);
    outline-offset: 2px;
}

@media (max-width: 420px) {
    #modalCheckout .modal-body {
        padding: 24px 18px !important;
    }

    #modalCheckout .btn {
        font-size: 13px;
    }
}
</style>


<!-- ==========================================
     JAVASCRIPT CHECK-OUT
========================================== -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('modalCheckout');
    const tombolKonfirmasi = document.getElementById(
        'btnKonfirmasiCheckout'
    );

    // Memastikan Bootstrap JS sudah dimuat.
    if (
        !modalElement ||
        !tombolKonfirmasi ||
        typeof bootstrap === 'undefined' ||
        !bootstrap.Modal
    ) {
        console.error(
            'Modal checkout gagal dimuat. Periksa Bootstrap Bundle JS.'
        );
        return;
    }

    const modalCheckout = bootstrap.Modal.getOrCreateInstance(
        modalElement
    );

    let formCheckoutAktif = null;
    let sedangMemproses = false;

    // Menampilkan modal ketika tombol checkout ditekan.
    document.querySelectorAll('.checkout-form').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            if (sedangMemproses) {
                return;
            }

            formCheckoutAktif = form;
            modalCheckout.show();
        });
    });

    // Mengirim form setelah pengguna mengonfirmasi.
    tombolKonfirmasi.addEventListener('click', function() {
        if (!formCheckoutAktif || sedangMemproses) {
            return;
        }

        sedangMemproses = true;

        tombolKonfirmasi.disabled = true;
        tombolKonfirmasi.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" ' +
            'role="status" aria-hidden="true"></span>' +
            'Memproses...';

        // Mengirim data dan token CSRF ke checkout_act.php.
        // Form.submit() menghindari pemanggilan ulang event submit.
        formCheckoutAktif.submit();
    });

    // Mengembalikan kondisi modal jika pengguna membatalkan.
    modalElement.addEventListener('hidden.bs.modal', function() {
        if (!sedangMemproses) {
            formCheckoutAktif = null;

            tombolKonfirmasi.disabled = false;
            tombolKonfirmasi.innerHTML =
                '<i class="bi bi-check-circle me-1"></i>' +
                'Ya, Check-out';
        }
    });
});
</script>

<?php require_once('../inc/footer.php'); ?>