<?php
require_once('../inc/auth.php');
cek_role(['admin']);
require_once('../inc/koneksi.php');

$dbConnection = new DatabaseConnection();
$conn = $dbConnection->getConnection();

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: list_staf.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM tb_staf WHERE id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$staf = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staf) {
    header('Location: list_staf.php');
    exit();
}

$base = '../';
$page_title = 'Edit Staf';

require_once('../inc/header.php');
?>

<div class="container-fluid px-0">
    <div class="d-flex align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">Edit Staf</h4>
            <small class="text-muted">Perbarui informasi staf dengan benar.</small>
        </div>
    </div>

    <?php if (!empty($_SESSION['message'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="editstaf_act.php" method="POST" id="formEditStaf">
                <?= csrf_field() ?>

                <input type="hidden" name="id" value="<?= (int) $staf['id'] ?>">

                <div class="mb-3">
                    <label for="nama_staf" class="form-label">
                        Nama Staf <span class="text-danger">*</span>
                    </label>

                    <input type="text" name="nama_staf" id="nama_staf" class="form-control"
                        value="<?= htmlspecialchars($staf['nama_staf'], ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Masukkan nama staf" minlength="2" maxlength="100"
                        pattern="(?=.*[A-Za-zÀ-ÖØ-öø-ÿ])[A-Za-zÀ-ÖØ-öø-ÿ0-9 .'-]+"
                        title="Nama harus mengandung huruf. Angka saja tidak diperbolehkan." autocomplete="name"
                        required>

                    <div class="form-text">
                        Nama harus mengandung huruf, bukan angka saja.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="departemen" class="form-label">
                        Departemen <span class="text-danger">*</span>
                    </label>

                    <input type="text" name="departemen" id="departemen" class="form-control"
                        value="<?= htmlspecialchars($staf['departemen'], ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Contoh: IT Support atau RPL 2" minlength="2" maxlength="100"
                        pattern="(?=.*[A-Za-zÀ-ÖØ-öø-ÿ])[A-Za-zÀ-ÖØ-öø-ÿ0-9 &.'-]+"
                        title="Departemen harus mengandung huruf. Angka saja tidak diperbolehkan." required>

                    <div class="form-text">
                        Departemen harus mengandung huruf. Contoh: IT Support atau RPL 2.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="submit" class="btn btn-dark">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>

                    <a href="list_staf.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('formEditStaf').addEventListener('submit', function(e) {
    const nama = document.getElementById('nama_staf').value.trim();
    const departemen = document.getElementById('departemen').value.trim();

    // Nama dan departemen wajib mengandung minimal satu huruf.
    const adaHuruf = /[A-Za-zÀ-ÖØ-öø-ÿ]/;

    if (!adaHuruf.test(nama)) {
        e.preventDefault();
        alert('Nama staf wajib mengandung huruf. Angka saja tidak diperbolehkan.');
        document.getElementById('nama_staf').focus();
        return;
    }

    if (!adaHuruf.test(departemen)) {
        e.preventDefault();
        alert('Departemen wajib mengandung huruf. Angka saja tidak diperbolehkan.');
        document.getElementById('departemen').focus();
    }
});
</script>

<?php require_once('../inc/footer.php'); ?>