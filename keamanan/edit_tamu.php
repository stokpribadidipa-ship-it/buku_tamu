<?php
// Memanggil file otentikasi dan membatasi akses khusus untuk peran keamanan.
require_once('../inc/auth.php');
cek_role(['keamanan']);

// Memanggil file koneksi database.
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

// Membuat fungsi pembantu 'e' untuk membersihkan teks dari karakter berbahaya guna mencegah celah XSS.
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

// Mengambil parameter ID tamu dari URL dan memvalidasinya sebagai angka bulat.
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

// Jika ID tidak valid, berikan peringatan dan alihkan ke dashboard.
if (!$id || $id < 1) {
    $_SESSION['message'] = 'ID tamu tidak valid.';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

try {
    // Menyiapkan query untuk mengambil data tamu berdasarkan ID yang statusnya masih di dalam gedung.
    $stmt = $conn->prepare(
        "SELECT *
         FROM tb_tamu
         WHERE id = :id
           AND status = 'di_dalam'
         LIMIT 1"
    );
    $stmt->execute([':id' => $id]);
    $tamu = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika data tamu tidak ditemukan, tampilkan peringatan.
    if (!$tamu) {
        $_SESSION['message'] =
            'Data tamu tidak ditemukan atau sudah check-out.';
        $_SESSION['message_type'] = 'warning';
        header('Location: index.php');
        exit;
    }

    // Mengambil daftar seluruh staf untuk ditampilkan sebagai pilihan pada elemen <select>.
    $stmtStaf = $conn->query(
        "SELECT id, nama_staf, departemen
         FROM tb_staf
         ORDER BY nama_staf ASC"
    );
    $list_staf = $stmtStaf->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Jika terjadi kesalahan database, catat ke log server.
    error_log('Edit tamu: ' . $e->getMessage());
    $_SESSION['message'] = 'Gagal mengambil data tamu.';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$base = '../';
$page_title = 'Edit Data Tamu';

require_once('../inc/header.php');
?>

<link rel="stylesheet" href="../asset/css/style.css">

<div class="container-fluid edit-guest-page">

    <div class="edit-page-heading">
        <div>
            <h4>Edit Data Tamu</h4>
            <p>Perbarui informasi tamu yang masih berada di dalam gedung.</p>
        </div>
    </div>

    <!-- Menampilkan pesan pemberitahuan jika ada pesan tersimpan di sesi -->
    <?php if (!empty($_SESSION['message'])): ?>
    <div class="alert alert-<?= e($_SESSION['message_type'] ?? 'info') ?> alert-dismissible fade show">
        <?= e($_SESSION['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    <?php
        unset($_SESSION['message'], $_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <div class="edit-guest-card">

        <div class="edit-guest-card-header">
            <div class="edit-guest-icon">
                <i class="bi bi-person-vcard"></i>
            </div>

            <div>
                <h5>Informasi Pengunjung</h5>
                <p>Periksa kembali data sebelum menyimpan perubahan.</p>
            </div>
        </div>

        <div class="edit-guest-card-body">

            <!-- Formulir pengubahan data dikirim menggunakan metode POST ke file edit_tamu_act.php -->
            <form action="edit_tamu_act.php" method="POST" autocomplete="off">
                <!-- Menyisipkan token pengaman CSRF -->
                <?= csrf_field() ?>

                <!-- Input tersembunyi untuk menyimpan ID tamu yang sedang diedit -->
                <input type="hidden" name="id" value="<?= e($tamu['id']) ?>">

                <div class="edit-section-title">
                    <i class="bi bi-person-lines-fill"></i>
                    Identitas Pengunjung
                </div>

                <div class="edit-form-grid">

                    <div class="edit-form-group">
                        <label for="nama_tamu">
                            Nama Tamu <span>*</span>
                        </label>
                        <input type="text" id="nama_tamu" name="nama_tamu" maxlength="150"
                            value="<?= e($tamu['nama_tamu'] ?? '') ?>" placeholder="Masukkan nama lengkap" required>
                    </div>

                    <div class="edit-form-group">
                        <label for="no_identitas">
                            Nomor Identitas (KTP/SIM) <span>*</span>
                        </label>
                        <input type="text" id="no_identitas" name="no_identitas" inputmode="numeric" maxlength="20"
                            value="<?= e($tamu['no_identitas'] ?? '') ?>" placeholder="Masukkan nomor identitas"
                            required>
                        <small>Masukkan nomor identitas tanpa spasi.</small>
                    </div>

                    <div class="edit-form-group">
                        <label for="no_telepon">
                            Nomor Telepon <span>*</span>
                        </label>
                        <input type="tel" id="no_telepon" name="no_telepon" maxlength="16"
                            value="<?= e($tamu['no_telepon'] ?? '') ?>" placeholder="Contoh: 081234567890" required>
                    </div>

                    <div class="edit-form-group">
                        <label for="instansi">Instansi</label>
                        <input type="text" id="instansi" name="instansi" maxlength="150"
                            value="<?= e($tamu['instansi'] ?? '') ?>" placeholder="Nama instansi atau perusahaan">
                    </div>

                    <div class="edit-form-group">
                        <label for="staf_dituju_id">
                            Staf yang Dituju <span>*</span>
                        </label>
                        <select id="staf_dituju_id" name="staf_dituju_id" required>
                            <option value="">-- Pilih Staf --</option>

                            <?php foreach ($list_staf as $staf): ?>
                            <option value="<?= e($staf['id']) ?>" <?= (string) ($staf['id']) ===
                                        (string) ($tamu['staf_dituju_id'] ?? '')
                                        ? 'selected'
                                        : '' ?>>
                                <?= e($staf['nama_staf']) ?>
                                (<?= e($staf['departemen']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="edit-form-group">
                        <label for="no_badge">
                            Nomor Badge <span>*</span>
                        </label>
                        <input type="text" id="no_badge" name="no_badge" maxlength="30"
                            value="<?= e($tamu['no_badge'] ?? '') ?>" placeholder="Contoh: P-05" required>
                    </div>

                    <div class="edit-form-group edit-form-full">
                        <label for="keperluan">Keperluan</label>
                        <textarea id="keperluan" name="keperluan" rows="3" maxlength="1000"
                            placeholder="Jelaskan tujuan kunjungan tamu..."><?= e($tamu['keperluan'] ?? '') ?></textarea>
                    </div>

                </div>

                <div class="edit-form-actions">
                    <a href="index.php" class="edit-btn edit-btn-secondary">
                        <i class="bi bi-x-circle"></i>
                        Batal
                    </a>

                    <button type="submit" class="edit-btn edit-btn-primary">
                        <i class="bi bi-check2-circle"></i>
                        Simpan Perubahan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php require_once('../inc/footer.php'); ?>